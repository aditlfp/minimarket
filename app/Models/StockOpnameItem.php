<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = [
        'stock_opname_id',
        'product_id',
        'qty_system',
        'qty_fisik',
        'selisih',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::saving(function (StockOpnameItem $item) {
            $warehouseId = $item->stockOpname->warehouse_id;
            $stock = \App\Models\Stock::where('product_id', $item->product_id)
                ->where('warehouse_id', $warehouseId)
                ->first();
            $qtySystem = $stock ? (float)$stock->qty : 0;
            $item->qty_system = $qtySystem;
            $item->selisih = (float)$item->qty_fisik - $qtySystem;
        });
    }
}
