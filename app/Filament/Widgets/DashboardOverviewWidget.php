<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class DashboardOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total Active Products
        $activeCount = Product::where('is_active', true)->count();
        $totalCount = Product::count();
        $percentage = $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 1) : 0;

        // Warehouse Capacity Summary
        $totalWarehouses = Warehouse::count();
        $activeWarehouses = Warehouse::where('is_active', true)->count();
        $totalCapacity = (float) Warehouse::sum('capacity_m3');
        $totalProductsInStock = (float) \Illuminate\Support\Facades\DB::table('product_warehouse')->sum('quantity_on_hand');
        $totalUsedCapacity = $totalProductsInStock;
        $availableCapacity = max(0, $totalCapacity - $totalUsedCapacity);
        $utilizationPercent = $totalCapacity > 0
            ? round(($totalUsedCapacity / $totalCapacity) * 100, 1)
            : 0;

        return [
            // Row 1: Products
            Stat::make('Total Active Products', $activeCount)
                ->description("{$percentage}% of {$totalCount} total products")
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary')
                ->chart([7, 12, 10, 14, 15, 18, 20]),

            Stat::make('Total Warehouses', $totalWarehouses)
                ->description("{$activeWarehouses} active")
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('info'),

            // Row 2: Capacity
            Stat::make('Total Capacity', number_format($totalCapacity, 2, ',', '.') . ' m³')
                ->description('Combined warehouse capacity')
                ->descriptionIcon('heroicon-o-cube')
                ->color('gray'),

            Stat::make('Used Capacity', number_format($totalUsedCapacity, 2, ',', '.') . ' m³')
                ->description("{$utilizationPercent}% utilized")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($utilizationPercent >= 80 ? 'danger' : ($utilizationPercent >= 50 ? 'warning' : 'success')),

            Stat::make('Available Capacity', number_format($availableCapacity, 2, ',', '.') . ' m³')
                ->description('Remaining space')
                ->descriptionIcon('heroicon-o-plus')
                ->color($availableCapacity > 0 ? 'success' : 'danger'),
        ];
    }
}
