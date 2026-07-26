<?php

namespace App\Filament\Resources\StockMovementResource\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StockMovementOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayMovements = StockMovement::today()->count();
        $inboundQuantity = StockMovement::today()->inbound()->sum('quantity');
        $outboundQuantity = abs(StockMovement::today()->outbound()->sum('quantity'));
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
