<?php
namespace App\Services;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(private StockService $stockService, private UnitConversionService $unitConversionService) {}

    public function createPurchaseOrder(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $total = 0;
            $items = $data['items'] ?? []; unset($data['items']);
            $data['created_by'] = Auth::id(); $data['status'] = 'draft';
            $po = PurchaseOrder::create($data);
            foreach ($items as $item) {
                $subtotal = (float)$item['qty'] * (float)$item['harga_satuan'];
                $total += $subtotal;
                $po->items()->create(['product_id' => $item['product_id'], 'unit_id' => $item['unit_id'], 'qty' => $item['qty'], 'harga_satuan' => $item['harga_satuan']]);
            }
            $po->update(['total' => $total]);
            return $po->fresh(['items', 'supplier', 'warehouse']);
        });
    }

    public function receiveGoods(PurchaseOrder $po, array $items, ?string $notes = null): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $items, $notes) {
            $receipt = GoodsReceipt::create(['purchase_order_id' => $po->id, 'received_by' => Auth::id(), 'received_at' => now(), 'notes' => $notes]);
            foreach ($items as $item) {
                $poItem = PurchaseOrderItem::findOrFail($item['purchase_order_item_id']);
                $qtyReceiving = (float)$item['qty_received'];
                $remaining = (float)$poItem->qty - $poItem->totalReceived();
                if ($qtyReceiving > $remaining) throw new \RuntimeException("Qty diterima melebihi sisa PO");

                $receiptItem = $receipt->items()->create(['purchase_order_item_id' => $poItem->id, 'qty_received' => $qtyReceiving, 'batch_number' => $item['batch_number'] ?? null, 'expired_date' => $item['expired_date'] ?? null]);

                $baseQty = $this->unitConversionService->toBaseUnit($poItem->product, $poItem->unit, $qtyReceiving);
                $this->stockService->addStock(product: $poItem->product, warehouse: $po->warehouse, qty: $baseQty, batchNumber: $item['batch_number'] ?? null, expiredDate: $item['expired_date'] ?? null, notes: "Penerimaan PO #{$po->id}", reference: $receiptItem);
            }
            $allItems = $po->items; $allReceived = true; $partialReceived = false;
            foreach ($allItems as $pi) {
                $remaining = (float)$pi->qty - $pi->totalReceived();
                if ($remaining > 0) { $allReceived = false; if ($pi->totalReceived() > 0) $partialReceived = true; }
            }
            $po->update(['status' => $allReceived ? 'completed' : ($partialReceived ? 'partial' : 'ordered')]);
            return $receipt->fresh(['items', 'purchaseOrder']);
        });
    }
}
