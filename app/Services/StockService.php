<?php
namespace App\Services;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock(Product $product, Warehouse $warehouse, float $qty, ?string $batchNumber = null, ?string $expiredDate = null, ?string $notes = null, ?object $reference = null): Stock
    {
        return DB::transaction(function () use ($product, $warehouse, $qty, $batchNumber, $expiredDate, $notes, $reference) {
            $stock = Stock::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['qty' => DB::raw("qty + {$qty}")]
            );
            $batchNo = $batchNumber ?: 'DEFAULT';
            ProductBatch::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'batch_number' => $batchNo],
                ['expired_date' => $expiredDate, 'qty' => DB::raw("qty + {$qty}")]
            );
            StockMovement::create([
                'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'type' => 'in', 'qty' => $qty,
                'reference_type' => $reference ? get_class($reference) : null, 'reference_id' => $reference?->id,
                'notes' => $notes, 'created_by' => Auth::id(),
            ]);
            return $stock->fresh();
        });
    }

    public function deductStock(Product $product, Warehouse $warehouse, float $qty, ?string $notes = null, ?object $reference = null): void
    {
        DB::transaction(function () use ($product, $warehouse, $qty, $notes, $reference) {
            $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->lockForUpdate()->first();
            if (!$stock || (float)$stock->qty < $qty)
                throw new \RuntimeException("Stok tidak mencukupi untuk produk {$product->nama}. Tersedia: " . ($stock->qty ?? 0) . ", diminta: {$qty}");

            $batches = ProductBatch::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->where('qty', '>', 0)
                ->orderByRaw('expired_date IS NULL ASC, expired_date ASC')->orderBy('created_at', 'asc')->lockForUpdate()->get();
            $remaining = $qty;

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;
                $batchQty = (float)$batch->qty;
                $deductFromBatch = min($batchQty, $remaining);
                $batch->decrement('qty', $deductFromBatch);
                $remaining -= $deductFromBatch;
                StockMovement::create([
                    'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'type' => 'out', 'qty' => $deductFromBatch,
                    'reference_type' => $reference ? get_class($reference) : null, 'reference_id' => $reference?->id,
                    'notes' => $notes ? "{$notes} (batch: {$batch->batch_number})" : "Batch: {$batch->batch_number}",
                    'created_by' => Auth::id(),
                ]);
            }
            if ($remaining > 0) throw new \RuntimeException("Stok tidak mencukupi (inkonsistensi data). Sisa: {$remaining}");
            $stock->decrement('qty', $qty);
        });
    }

    public function getStock(Product $product, Warehouse $warehouse): float
    {
        $stock = Stock::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->first();
        return $stock ? (float)$stock->qty : 0;
    }

    public function approveOpname(\App\Models\StockOpname $opname): void
    {
        DB::transaction(function () use ($opname) {
            if ($opname->status !== 'draft') {
                throw new \RuntimeException('Hanya Stock Opname berstatus draft yang dapat disetujui.');
            }
            
            $opname->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
            ]);

            foreach ($opname->items as $item) {
                $selisih = (float)$item->selisih;
                if ($selisih == 0) continue;

                if ($selisih > 0) {
                    $this->addStock(
                        product: $item->product,
                        warehouse: $opname->warehouse,
                        qty: $selisih,
                        notes: "Adjustment Opname #{$opname->id} (Kelebihan fisik)",
                        reference: $opname
                    );
                } else {
                    $this->deductStock(
                        product: $item->product,
                        warehouse: $opname->warehouse,
                        qty: abs($selisih),
                        notes: "Adjustment Opname #{$opname->id} (Kekurangan fisik)",
                        reference: $opname
                    );
                }
            }
        });
    }
}
