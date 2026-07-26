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
        $totalWarehouses = Warehouse::count();
        $activeCount = Warehouse::where('is_active', true)->count();
        $percentage = $totalWarehouses > 0 ? round(($activeCount / $totalWarehouses) * 100, 1) : 0;

        // Calculate total capacity
        $totalCapacity = Warehouse::sum('capacity_m3');

        // Calculate total products in stock across all warehouses
        $totalProductsInStock = Product::get()
            ->sum(function ($product) {
                return $product->totalQuantityOnHand();
            });

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
