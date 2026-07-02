<?php
namespace App\Services;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(private StockService $stockService) {}

    public function approve(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer->update(['status' => 'in_transit', 'approved_by' => Auth::id()]);
            foreach ($transfer->items as $item) {
                $this->stockService->deductStock(product: $item->product, warehouse: $transfer->fromWarehouse, qty: (float)$item->qty, notes: "Transfer ke {$transfer->toWarehouse->nama}", reference: $transfer);
            }
        });
    }

    public function receive(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $transfer->update(['status' => 'received', 'received_by' => Auth::id()]);
            foreach ($transfer->items as $item) {
                $this->stockService->addStock(product: $item->product, warehouse: $transfer->toWarehouse, qty: (float)$item->qty, notes: "Penerimaan transfer dari {$transfer->fromWarehouse->nama}", reference: $transfer);
            }
        });
    }

    public function reject(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            if ($transfer->status === 'in_transit') {
                foreach ($transfer->items as $item) {
                    $this->stockService->addStock(product: $item->product, warehouse: $transfer->fromWarehouse, qty: (float)$item->qty, notes: "Reject transfer ke {$transfer->toWarehouse->nama}", reference: $transfer);
                }
            }
            $transfer->update(['status' => 'rejected']);
        });
    }
}
