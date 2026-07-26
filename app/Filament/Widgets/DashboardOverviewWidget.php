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
        // Single query with conditional aggregation for product counts
        $productStats = Product::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
        ')->first();

        $totalCount = $productStats->total ?? 0;
        $activeCount = $productStats->active ?? 0;
        $percentage = $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 1) : 0;

        // Single query with conditional aggregation for warehouse counts and capacity
        $warehouseStats = Warehouse::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(capacity_m3) as total_capacity
        ')->first();

        $totalWarehouses = $warehouseStats->total ?? 0;
        $activeWarehouses = $warehouseStats->active ?? 0;
        $totalCapacity = (float) ($warehouseStats->total_capacity ?? 0);

        // Calculate total products in stock using SQL aggregation
        $totalProductsInStock = (float) \Illuminate\Support\Facades\DB::table('product_warehouse')
            ->sum('quantity_on_hand');
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
