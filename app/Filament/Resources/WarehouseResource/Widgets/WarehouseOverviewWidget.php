<?php

namespace App\Filament\Resources\WarehouseResource\Widgets;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class WarehouseOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Single query with conditional aggregation for warehouse counts and capacity
        $warehouseStats = Warehouse::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(capacity_m3) as total_capacity
        ')->first();

        $totalWarehouses = $warehouseStats->total ?? 0;
        $activeCount = $warehouseStats->active ?? 0;
        $totalCapacity = $warehouseStats->total_capacity ?? 0;
        $percentage = $totalWarehouses > 0 ? round(($activeCount / $totalWarehouses) * 100, 1) : 0;

        // Calculate total products in stock using SQL aggregation (not loading all products to memory)
        $totalProductsInStock = \Illuminate\Support\Facades\DB::table('product_warehouse')
            ->sum('quantity_on_hand') ?? 0;

        return [
            Stat::make('Total Warehouses', $totalWarehouses)
                ->description('All warehouses in system')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Active Warehouses', $activeCount)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Capacity', number_format($totalCapacity, 2, ',', '.') . ' m³')
                ->description('Combined warehouse capacity')
                ->descriptionIcon('heroicon-o-cube')
                ->color('warning'),

            Stat::make('Total Products in Stock', $totalProductsInStock)
                ->description('Across all warehouses')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),
        ];
    }
}
