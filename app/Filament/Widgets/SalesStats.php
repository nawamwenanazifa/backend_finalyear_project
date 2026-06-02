<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = Order::where('payment_status', 'paid')
            ->sum('total');
        
        $totalOrders = Order::count();
        
        $totalCustomers = User::where('is_admin', false)->count();
        
        $lowStockProducts = Product::where('stock_quantity', '<=', 5)
            ->where('stock_quantity', '>', 0)
            ->count();
        
        $outOfStockProducts = Product::where('stock_quantity', 0)->count();

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