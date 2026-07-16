<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'half';
    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('admin') || $user->hasRole('manajer'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SaleItem::query()
                ->select('product_id', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_revenue'))
                ->with('product:id,nama')
                ->whereHas('sale', fn ($q) => $q->where('status', 'completed'))
                ->groupBy('product_id')->orderByDesc('total_qty')->limit(10))
            ->columns([
                Tables\Columns\TextColumn::make('product.nama')->label('Produk'),
                Tables\Columns\TextColumn::make('total_qty')->label('Terjual')->sortable(),
                Tables\Columns\TextColumn::make('total_revenue')->label('Pendapatan')->money('IDR'),
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return (string) $record->product_id;
    }
}
