<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class SalesStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        try {
            $totalRevenue = Schema::hasTable('orders')
                ? Order::where('payment_status', 'paid')->sum('total')
                : 0;
            
            $totalOrders = Schema::hasTable('orders')
                ? Order::count()
                : 0;
            
            $totalCustomers = User::where('is_admin', false)->count();
            
            $lowStockProducts = Schema::hasTable('products')
                ? Product::where('stock_quantity', '<=', 5)
                    ->where('stock_quantity', '>', 0)
                    ->count()
                : 0;
            
            $outOfStockProducts = Schema::hasTable('products')
                ? Product::where('stock_quantity', 0)->count()
                : 0;
        } catch (\Exception $e) {
            $totalRevenue = 0;
            $totalOrders = 0;
            $totalCustomers = 0;
            $lowStockProducts = 0;
            $outOfStockProducts = 0;
        }

        return [
            Stat::make('Total Revenue', 'UGX ' . number_format($totalRevenue, 0))
                ->description('From paid orders')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            
            Stat::make('Total Orders', $totalOrders)
                ->description('All time')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
            
            Stat::make('Customers', $totalCustomers)
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),
            
            Stat::make('Low Stock Alert', $lowStockProducts)
                ->description("$outOfStockProducts out of stock")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockProducts > 0 ? 'danger' : 'success'),
        ];
    }
}