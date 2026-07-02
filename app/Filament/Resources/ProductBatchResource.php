<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductBatchResource\Pages;
use App\Models\ProductBatch;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductBatchResource extends Resource
{
    protected static ?string $model = ProductBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expired_date')
            ->columns([
                Tables\Columns\TextColumn::make('product.nama'),
                Tables\Columns\TextColumn::make('warehouse.nama'),
                Tables\Columns\TextColumn::make('batch_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->date()
                    ->color(function (?ProductBatch $record): ?string {
                        if ($record === null || $record->expired_date === null) return null;
                        $diff = now()->diffInDays($record->expired_date, false);
                        if ($diff < 0) return 'danger';
                        if ($diff <= 30) return 'warning';
                        return null;
                    }),
                Tables\Columns\TextColumn::make('qty')
                    ->numeric(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->relationship('warehouse', 'nama')
                    ->label('Warehouse'),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired (<= 30 hari)')
                    ->query(fn(Builder $query): Builder => $query->where('expired_date', '<=', now()->addDays(30))),
            ])
            ->actions([])
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
            'index' => Pages\ListProductBatches::route('/'),
        ];
    }
}
