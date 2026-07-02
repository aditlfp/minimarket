<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('from_warehouse_id')
                    ->relationship('fromWarehouse', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Dari Warehouse'),
                Forms\Components\Select::make('to_warehouse_id')
                    ->relationship('toWarehouse', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Ke Warehouse'),
                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->createItemButtonLabel('Tambah Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromWarehouse.nama')
                    ->label('Dari'),
                Tables\Columns\TextColumn::make('toWarehouse.nama')
                    ->label('Ke'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_transit' => 'warning',
                        'received' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn(StockTransfer $record): bool => $record->status === 'pending')
                    ->action(function (StockTransfer $record, StockTransferService $service) {
                        try {
                            $service->approve($record);
                            Notification::make()->title('Transfer disetujui')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('receive')
                    ->label('Receive')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->color('success')
                    ->visible(fn(StockTransfer $record): bool => $record->status === 'in_transit')
                    ->action(function (StockTransfer $record, StockTransferService $service) {
                        try {
                            $service->receive($record);
                            Notification::make()->title('Barang diterima')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(StockTransfer $record): bool => in_array($record->status, ['pending', 'in_transit']))
                    ->action(function (StockTransfer $record, StockTransferService $service) {
                        try {
                            $service->reject($record);
                            Notification::make()->title('Transfer ditolak')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
