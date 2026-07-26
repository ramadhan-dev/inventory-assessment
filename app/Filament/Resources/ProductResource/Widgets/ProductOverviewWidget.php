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
        // Single query with conditional aggregation for product counts
        $productStats = Product::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
        ')->first();

        $totalProducts = $productStats->total ?? 0;
        $activeCount = $productStats->active ?? 0;
        $percentage = $totalProducts > 0 ? round(($activeCount / $totalProducts) * 100, 1) : 0;

        // Calculate total stock value using SQL aggregation (not loading all products to memory)
        $totalValue = \Illuminate\Support\Facades\DB::table('products')
            ->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
            ->selectRaw('SUM(products.unit_price * product_warehouse.quantity_on_hand) as total_value')
            ->value('total_value') ?? 0;

        // Count warehouses with stock using JOIN instead of whereHas
        $warehouseStats = Warehouse::selectRaw('
            COUNT(*) as total,
            COUNT(DISTINCT CASE WHEN pw.quantity_on_hand > 0 THEN pw.warehouse_id END) as with_stock
        ')
            ->leftJoin('product_warehouse as pw', 'warehouses.id', '=', 'pw.warehouse_id')
            ->first();

        $warehousesWithStock = $warehouseStats->with_stock ?? 0;
        $totalWarehouses = $warehouseStats->total ?? 0;

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
