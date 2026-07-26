<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use App\Models\Warehouse;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ProductOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $activeCount = Product::where('is_active', true)->count();
        $percentage = $totalProducts > 0 ? round(($activeCount / $totalProducts) * 100, 1) : 0;

        // Calculate total stock value: sum(unit_price * total_quantity_on_hand)
        $totalValue = Product::get()
            ->sum(function ($product) {
                return $product->unit_price * $product->totalQuantityOnHand();
            });

        // Count warehouses that have at least one product with stock
        $warehousesWithStock = Warehouse::whereHas('products', function ($query) {
            $query->where('product_warehouse.quantity_on_hand', '>', 0);
        })->count();

        $totalWarehouses = Warehouse::count();

        return [
            Stat::make('Total Products', $totalProducts)
                ->description('All products in system')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Active Products', $activeCount)
                ->description("{$percentage}% of total")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Stock Value', 'IDR ' . number_format($totalValue, 0, ',', '.'))
                ->description('Unit price × quantity on hand')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('warning'),

            Stat::make('Warehouses with Stock', $warehousesWithStock)
                ->description("of {$totalWarehouses} total warehouses")
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('info'),
        ];
    }
}
