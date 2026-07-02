<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOpnameResource\Pages;
use App\Models\StockOpname;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockOpnameResource extends Resource
{
    protected static ?string $model = StockOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->relationship('warehouse', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
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
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('qty_system')
                                    ->numeric()
                                    ->disabled()
                                    ->label('Qty Sistem')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('qty_fisik')
                                    ->numeric()
                                    ->required()
                                    ->label('Qty Fisik')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('selisih')
                                    ->numeric()
                                    ->disabled()
                                    ->label('Selisih')
                                    ->columnSpan(1),
                            ])
                            ->columns(5)
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
                 Tables\Columns\TextColumn::make('warehouse.nama'),
                 Tables\Columns\TextColumn::make('status')
                     ->badge()
                     ->color(fn(string $state): string => match ($state) {
                         'draft' => 'gray',
                         'approved' => 'success',
                         default => 'gray',
                     }),
             ])
             ->filters([
                 //
             ])
             ->actions([
                 Tables\Actions\Action::make('approve')
                     ->label('Approve')
                     ->icon('heroicon-o-check-circle')
                     ->color('success')
                     ->visible(fn(StockOpname $record): bool => $record->status === 'draft')
                     ->action(function (StockOpname $record, \App\Services\StockService $service) {
                         try {
                             $service->approveOpname($record);
                             \Filament\Notifications\Notification::make()->title('Stock Opname berhasil disetujui')->success()->send();
                         } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                         }
                     }),
                 Tables\Actions\EditAction::make()
                     ->visible(fn(StockOpname $record): bool => $record->status === 'draft'),
                 Tables\Actions\DeleteAction::make()
                     ->visible(fn(StockOpname $record): bool => $record->status === 'draft'),
             ])
             ->bulkActions([
                 Tables\Actions\BulkActionGroup::make([
                     Tables\Actions\DeleteBulkAction::make(),
                 ]),
             ]);
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
            'index' => Pages\ListStockOpnames::route('/'),
            'create' => Pages\CreateStockOpname::route('/create'),
            'edit' => Pages\EditStockOpname::route('/{record}/edit'),
        ];
    }
}
