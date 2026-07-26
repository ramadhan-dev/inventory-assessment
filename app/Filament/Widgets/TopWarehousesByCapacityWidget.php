<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopWarehousesByCapacityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Top 5 Warehouses by Capacity Utilization';

    protected int|string|array $columnSpan = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // utilization_percent dihitung langsung di SQL lewat JOIN + SUM,
                // bukan tarik semua warehouse + semua produknya ke PHP lalu
                // di-map/sort manual (yang mengubah Builder jadi Collection dan
                // berat kalau data sudah besar).
                Warehouse::query()
                    ->select('warehouses.*')
                    ->selectRaw(
                        'CASE
                            WHEN warehouses.capacity_m3 > 0
                            THEN ROUND((COALESCE(SUM(product_warehouse.quantity_on_hand), 0) / warehouses.capacity_m3) * 100, 2)
                            ELSE 0
                        END as utilization_percent'
                    )
                    ->leftJoin('product_warehouse', 'product_warehouse.warehouse_id', '=', 'warehouses.id')
                    ->groupBy('warehouses.id')
                    ->orderByDesc('utilization_percent')
                    ->limit(5)
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
            // static top-5, bukan tabel yang perlu di-paginate
            ->paginated(false);
    }
}