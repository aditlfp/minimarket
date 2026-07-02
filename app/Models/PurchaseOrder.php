<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToOutlet;

    protected $fillable = [
        'supplier_id',
        'outlet_id',
        'warehouse_id',
        'status',
        'total',
        'created_by',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum(\Illuminate\Support\Facades\DB::raw('qty * harga_satuan'));
        $this->updateQuietly(['total' => $total]);
    }

    protected static function booted()
    {
        static::creating(function (PurchaseOrder $po) {
            $po->status = $po->status ?? 'draft';
            $po->total = $po->total ?? 0;
            $po->created_by = $po->created_by ?? auth()->id();
        });
    }
}
