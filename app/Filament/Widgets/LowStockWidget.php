<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'half';
    protected static bool $isLazy = true;

    public function table(Table $table): Table
    {
        return $table
            ->query(Stock::with('product', 'warehouse')->where('qty', '<=', 5)->orderBy('qty'))
            ->columns([
                Tables\Columns\TextColumn::make('product.nama')->label('Produk')->searchable(),
                Tables\Columns\TextColumn::make('warehouse.nama')->label('Gudang'),
                Tables\Columns\TextColumn::make('qty')->label('Stok')->color('danger')->weight('bold'),
            ]);
    }
}
