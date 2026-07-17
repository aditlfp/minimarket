<?php
namespace App\Services;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(private StockService $stockService, private UnitConversionService $unitConversionService) {}

    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $cashRegister = CashRegister::findOrFail($data['cash_register_id']);
            if ($cashRegister->status !== 'open') {
                throw new \RuntimeException('Cash register harus dalam status open.');
            }
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            $productIds = collect($data['items'])->pluck('product_id')->unique()->all();
            $products = \App\Models\Product::with('conversions')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            $unitIds = collect($data['items'])->pluck('unit_id')->filter()->unique()->all();
            $units = \App\Models\ProductUnit::whereIn('id', $unitIds)->get()->keyBy('id');

            $invoiceNumber = 'INV-'.date('Ymd').'-'.str_pad((string) (Sale::max('id') + 1), 4, '0', STR_PAD_LEFT);
            $subtotal = 0;
            $saleItems = [];

            foreach ($data['items'] as $item) {
                $product = $products->get($item['product_id']);
                if (! $product) {
                    throw new \RuntimeException("Produk #{$item['product_id']} tidak ditemukan.");
                }
                $unitId = (int) ($item['unit_id'] ?? $product->base_unit_id);
                $unit = $units->get($unitId) ?? $units->get((string) $unitId) ?? \App\Models\ProductUnit::findOrFail($unitId);
                $qty = (float) $item['qty'];
                $price = (float) ($item['harga_satuan'] ?? $product->harga_jual);
                $baseQty = $this->unitConversionService->toBaseUnit($product, $unit, $qty);
                $this->stockService->deductStock(product: $product, warehouse: $warehouse, qty: $baseQty, notes: "Penjualan {$invoiceNumber}");
                $lineSubtotal = $qty * $price;
                $subtotal += $lineSubtotal;
                $saleItems[] = ['product_id' => $product->id, 'unit_id' => $unit->id, 'qty' => $qty, 'harga_satuan' => $price, 'subtotal' => $lineSubtotal];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $total = $subtotal - $discount + $tax;
            $sale = Sale::create([
                'outlet_id' => $data['outlet_id'],
                'warehouse_id' => $warehouse->id,
                'cash_register_id' => $cashRegister->id,
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => 'completed',
                'created_by' => Auth::id(),
            ]);
            $sale->items()->createMany($saleItems);
            $sale->payments()->createMany(collect($data['payments'])->map(fn ($p) => [
                'payment_method' => $p['method'],
                'amount' => (float) $p['amount'],
            ])->all());

            return $sale->fresh(['items', 'payments', 'customer']);
        });
    }

    public function voidSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            if ($sale->status === 'void') throw new \RuntimeException('Transaksi sudah di-void.');
            $warehouse = Warehouse::findOrFail($sale->warehouse_id);
            foreach ($sale->items as $item) {
                $unit = $item->unit ?? $item->product->baseUnit;
                $baseQty = $this->unitConversionService->toBaseUnit($item->product, $unit, (float)$item->qty);
                $this->stockService->addStock(product: $item->product, warehouse: $warehouse, qty: $baseQty, notes: "Void {$sale->invoice_number}", reference: $sale);
            }
            $sale->update(['status' => 'void']);
        });
    }

    public function processReturn(Sale $sale, array $items, ?string $reason = null): SaleReturn
    {
        return DB::transaction(function () use ($sale, $items, $reason) {
            $warehouse = Warehouse::findOrFail($sale->warehouse_id); $totalRefund = 0;
            $saleReturn = SaleReturn::create(['sale_id' => $sale->id, 'reason' => $reason, 'total_refund' => 0, 'created_by' => Auth::id()]);
            foreach ($items as $item) {
                $saleItem = SaleItem::with('product')->findOrFail($item['sale_item_id']);
                $qtyReturn = (float)$item['qty']; $unit = $saleItem->unit ?? $saleItem->product->baseUnit;
                $baseQty = $this->unitConversionService->toBaseUnit($saleItem->product, $unit, $qtyReturn);
                $this->stockService->addStock(product: $saleItem->product, warehouse: $warehouse, qty: $baseQty, notes: "Retur {$sale->invoice_number}", reference: $saleReturn);
                $subtotal = $qtyReturn * (float)$saleItem->harga_satuan; $totalRefund += $subtotal;
                $saleReturn->items()->create(['sale_item_id' => $saleItem->id, 'product_id' => $saleItem->product_id, 'qty' => $qtyReturn, 'harga_satuan' => $saleItem->harga_satuan, 'subtotal' => $subtotal]);
            }
            $saleReturn->update(['total_refund' => $totalRefund]);
            return $saleReturn->fresh(['items', 'sale']);
        });
    }
}
