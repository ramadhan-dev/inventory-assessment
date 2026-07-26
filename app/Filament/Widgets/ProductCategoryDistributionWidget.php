<?php

namespace App\Filament\Widgets;

use App\Enums\ProductCategory;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class ProductCategoryDistributionWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Product Distribution by Category';

    public function getStats(): array
    {
        // 1 query dengan GROUP BY, bukan 4 query COUNT() terpisah —
        // lebih ringan saat tabel products sudah besar (Section D).
        $counts = Product::query()
            ->where('is_active', true)
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $rawMaterialCount = (int) ($counts[ProductCategory::RawMaterial->value] ?? 0);
        $finishedGoodsCount = (int) ($counts[ProductCategory::FinishedGoods->value] ?? 0);
        $packagingCount = (int) ($counts[ProductCategory::Packaging->value] ?? 0);
        $sparePartCount = (int) ($counts[ProductCategory::SparePart->value] ?? 0);

        $total = $rawMaterialCount + $finishedGoodsCount + $packagingCount + $sparePartCount;

        $percentage = fn (int $count): string => $total > 0
            ? round(($count / $total) * 100, 1).'%'
            : '0%';

        return [
            Stat::make('Raw Material', $rawMaterialCount)
                ->description($percentage($rawMaterialCount))
                ->descriptionIcon('heroicon-o-cube')
                ->color('gray'),

            Stat::make('Finished Goods', $finishedGoodsCount)
                ->description($percentage($finishedGoodsCount))
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Packaging', $packagingCount)
                ->description($percentage($packagingCount))
                ->descriptionIcon('heroicon-o-cube')
                ->color('warning'),

            Stat::make('Spare Part', $sparePartCount)
                ->description($percentage($sparePartCount))
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),
        ];
    }
}