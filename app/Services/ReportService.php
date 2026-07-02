<?php
namespace App\Services;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use Carbon\Carbon;

class ReportService
{
    public function salesReport(?int $outletId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Sale::with('items.product', 'payments', 'outlet')->where('status', 'completed');
        if ($outletId) $query->where('outlet_id', $outletId);
        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);
        $sales = $query->get();
        return ['total_sales' => $sales->count(), 'total_revenue' => $sales->sum('total'), 'total_discount' => $sales->sum('discount'), 'average_per_sale' => $sales->avg('total') ?: 0, 'sales' => $sales];
    }

    public function stockReport(?int $warehouseId = null): array
    {
        $query = Stock::with('product.category', 'warehouse.outlet');
        if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        $stocks = $query->get();
        return ['total_products' => $stocks->count(), 'total_value' => $stocks->sum(fn($s) => $s->qty * $s->product->harga_beli), 'low_stock' => $stocks->filter(fn($s) => $s->qty <= 5), 'stocks' => $stocks];
    }

    public function profitReport(?int $outletId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Sale::with('items.product', 'outlet')->where('status', 'completed');
        if ($outletId) $query->where('outlet_id', $outletId);
        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);
        $sales = $query->get();
        $grossProfit = 0; $costOfGoods = 0;
        foreach ($sales as $sale) { foreach ($sale->items as $item) { $cost = (float)$item->product->harga_beli * (float)$item->qty; $revenue = (float)$item->subtotal; $costOfGoods += $cost; $grossProfit += ($revenue - $cost); } }
        $expenseQuery = Expense::query();
        if ($outletId) $expenseQuery->where('outlet_id', $outletId);
        if ($startDate) $expenseQuery->where('tanggal', '>=', $startDate);
        if ($endDate) $expenseQuery->where('tanggal', '<=', $endDate);
        $totalExpenses = (float)$expenseQuery->sum('amount');
        return ['total_revenue' => $sales->sum('total'), 'cost_of_goods' => $costOfGoods, 'gross_profit' => $grossProfit, 'total_expenses' => $totalExpenses, 'net_profit' => $grossProfit - $totalExpenses, 'margin' => $sales->sum('total') > 0 ? ($grossProfit / $sales->sum('total')) * 100 : 0];
    }

    public function stockCard(int $productId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = StockMovement::with('warehouse')->where('product_id', $productId);
        if ($startDate) $query->where('created_at', '>=', $startDate);
        if ($endDate) $query->where('created_at', '<=', $endDate);
        $movements = $query->orderBy('created_at', 'desc')->get();
        return ['product_id' => $productId, 'movements' => $movements, 'total_in' => $movements->where('type', 'in')->sum('qty'), 'total_out' => $movements->where('type', 'out')->sum('qty')];
    }
}
