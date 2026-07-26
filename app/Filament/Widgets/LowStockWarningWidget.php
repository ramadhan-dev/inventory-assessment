<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockWarningWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = '⚠️ Low Stock Warnings (< 10 units)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->where('is_active', true)
                    ->select('products.*')
                    ->selectRaw('COALESCE(SUM(product_warehouse.quantity_on_hand), 0) as total_quantity_on_hand')
                    ->leftJoin('product_warehouse', 'product_warehouse.product_id', '=', 'products.id')
                    ->groupBy('products.id')
                    ->havingRaw('COALESCE(SUM(product_warehouse.quantity_on_hand), 0) > 0')
                    ->havingRaw('COALESCE(SUM(product_warehouse.quantity_on_hand), 0) < 10')
            )
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('total_quantity_on_hand')
                    ->label('Total Stock')
                    ->numeric()
                    ->sortable()
                    ->color('warning')
                    ->badge()
                    ->description('Low stock threshold: 10'),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25]);
    }
}