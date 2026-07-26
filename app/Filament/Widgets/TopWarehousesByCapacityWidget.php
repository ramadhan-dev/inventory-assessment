<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopWarehousesByCapacityWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Top 5 Warehouses by Capacity Utilization';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Warehouse::query()
                    ->with('products')
                    ->get()
                    ->map(function ($warehouse) {
                        $warehouse->utilization_percent = $warehouse->capacityUtilizationPercent();
                        return $warehouse;
                    })
                    ->sortByDesc('utilization_percent')
                    ->take(5)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->label('Location')
                    ->searchable(),

                TextColumn::make('capacity_m3')
                    ->label('Capacity (m³)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('utilization_percent')
                    ->label('Utilization %')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->color(fn ($state): string => $state >= 80 ? 'danger' : ($state >= 50 ? 'warning' : 'success'))
                    ->sortable(),
            ])
            ->defaultSort('utilization_percent', 'desc');
    }
}
