<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Services\PurchaseService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi PO')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('outlet_id')
                            ->relationship('outlet', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('warehouse_id')
                            ->relationship('warehouse', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
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
                                Forms\Components\Select::make('unit_id')
                                    ->relationship('unit', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('harga_satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
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
                Tables\Columns\TextColumn::make('supplier.nama'),
                Tables\Columns\TextColumn::make('outlet.nama'),
                Tables\Columns\TextColumn::make('warehouse.nama'),
                Tables\Columns\TextColumn::make('total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'ordered' => 'info',
                        'partial' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(PurchaseOrder $record): bool => $record->status === 'draft'),
                Tables\Actions\Action::make('terimaBarang')
                    ->label('Terima Barang')
                    ->icon('heroicon-o-check-circle')
                    ->form(function (PurchaseOrder $record) {
                        return $record->items->map(function ($item) {
                            return Forms\Components\Section::make($item->product?->nama ?? 'Product #' . $item->product_id)
                                ->schema([
                                    Forms\Components\Hidden::make('items.' . $item->id . '.purchase_order_item_id')
                                        ->default($item->id),
                                    Forms\Components\TextInput::make('items.' . $item->id . '.qty_received')
                                        ->label('Qty Diterima')
                                        ->numeric()
                                        ->required()
                                        ->default($item->qty - $item->totalReceived()),
                                    Forms\Components\TextInput::make('items.' . $item->id . '.batch_number')
                                        ->label('Batch Number'),
                                    Forms\Components\DatePicker::make('items.' . $item->id . '.expired_date')
                                        ->label('Expired Date'),
                                ])->columns(3);
                        })->toArray();
                    })
                    ->action(function (PurchaseOrder $record, array $data, PurchaseService $purchaseService) {
                        try {
                            $items = collect($data['items'] ?? [])->values()->toArray();
                            $purchaseService->receiveGoods($record, $items);
                            Notification::make()->title('Barang berhasil diterima')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
