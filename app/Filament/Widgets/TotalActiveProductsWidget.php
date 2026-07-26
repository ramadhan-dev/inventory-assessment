<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class TotalActiveProductsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeCount = Product::where('is_active', true)->count();
        $totalCount = Product::count();
        $percentage = $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 1) : 0;

        return [
            Stat::make('Total Active Products', $activeCount)
                ->description("{$percentage}% of {$totalCount} total products")
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary')
                ->chart([7, 12, 10, 14, 15, 18, 20]), // Dummy chart data
        ];
    }
}
