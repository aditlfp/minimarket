<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'unit_id',
        'qty',
        'harga_satuan',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function totalReceived(): int
    {
        return GoodsReceiptItem::where('purchase_order_item_id', $this->id)->sum('qty_received');
    }

    protected static function booted()
    {
        static::saved(function (PurchaseOrderItem $item) {
            if ($item->purchaseOrder) {
                $item->purchaseOrder->recalculateTotal();
            }
        });

        static::deleted(function (PurchaseOrderItem $item) {
            if ($item->purchaseOrder) {
                $item->purchaseOrder->recalculateTotal();
            }
        });
    }
}
