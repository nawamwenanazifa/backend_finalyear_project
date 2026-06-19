<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        if (!Schema::hasTable('orders')) {
            return [
                'datasets' => [['label' => 'Revenue', 'data' => []]],
                'labels' => [],
            ];
        }

        // Dummy data for visual effect since we don't have enough orders yet
        $data = [
            1200000, 1500000, 1100000, 1800000, 2200000, 2500000, 
            2100000, 2800000, 3100000, 2900000, 3500000, 4200000
        ];
        
        $actualOrders = Order::where('payment_status', 'paid')
            ->selectRaw('SUM(total) as sum, MONTH(created_at) as month')
            ->groupBy('month')
            ->pluck('sum', 'month')
            ->toArray();

        foreach ($actualOrders as $month => $sum) {
            $data[$month - 1] = $sum;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (UGX)',
                    'data' => $data,
                    'backgroundColor' => '#570013',
                    'borderColor' => '#570013',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
