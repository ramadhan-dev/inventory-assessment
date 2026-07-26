<?php

namespace App\Filament\Resources\StockMovementResource\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StockMovementOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Single query with conditional aggregation for all movement stats
        $stats = StockMovement::whereDate('created_at', today())
            ->selectRaw('
                COUNT(*) as total_movements,
                SUM(CASE WHEN movement_type = "in" THEN quantity ELSE 0 END) as inbound_quantity,
                SUM(CASE WHEN movement_type = "out" THEN ABS(quantity) ELSE 0 END) as outbound_quantity
            ')
            ->first();

        $todayMovements = $stats->total_movements ?? 0;
        $inboundQuantity = $stats->inbound_quantity ?? 0;
        $outboundQuantity = $stats->outbound_quantity ?? 0;
        $netMovement = $inboundQuantity - $outboundQuantity;

        return [
            Stat::make('Total Movements Today', $todayMovements)
                ->description('All movements today')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('primary'),

            Stat::make('Inbound Today', $inboundQuantity)
                ->description('Stock in quantity')
                ->descriptionIcon('heroicon-o-arrow-down')
                ->color('success'),

            Stat::make('Outbound Today', $outboundQuantity)
                ->description('Stock out quantity')
                ->descriptionIcon('heroicon-o-arrow-up')
                ->color('danger'),

            Stat::make('Net Movement', $netMovement)
                ->description('Inbound - Outbound')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color($netMovement >= 0 ? 'success' : 'danger'),
        ];
    }
}
