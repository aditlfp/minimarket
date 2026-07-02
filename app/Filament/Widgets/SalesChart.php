<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    public ?string $filter = '30';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('admin') || $user->hasRole('manajer'));
    }

    protected function getFilters(): ?array
    {
        return ['7' => '7 Hari', '30' => '30 Hari', '90' => '90 Hari'];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 30);
        $sales = Sale::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')->orderBy('date')->pluck('total', 'date');
        return [
            'datasets' => [[
                'label' => 'Penjualan', 'data' => $sales->values()->toArray(),
                'borderColor' => '#3b82f6', 'backgroundColor' => '#93c5fd',
            ]],
            'labels' => $sales->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
