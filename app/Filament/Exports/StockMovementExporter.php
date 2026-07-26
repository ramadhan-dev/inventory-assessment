<?php

namespace App\Filament\Exports;

use App\Models\StockMovement;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockMovementExporter extends Exporter
{
    protected static ?string $model = StockMovement::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('created_at')
                ->label('Date')
                ->formatStateUsing(fn ($state) => $state->format('d M Y H:i')),
            ExportColumn::make('product.sku')
                ->label('SKU'),
            ExportColumn::make('product.name')
                ->label('Product'),
            ExportColumn::make('warehouse.name')
                ->label('Warehouse'),
            ExportColumn::make('movement_type')
                ->label('Movement Type')
                ->formatStateUsing(fn ($state) => strtoupper($state)),
            ExportColumn::make('quantity')
                ->label('Quantity'),
            ExportColumn::make('reference_number')
                ->label('Reference Number'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('moved_by')
                ->label('Moved By'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "Your stock movement export has completed and " . number_format($export->total_rows) . " " . str('row')->plural($export->total_rows) . " exported.";

        return $body;
    }
}
