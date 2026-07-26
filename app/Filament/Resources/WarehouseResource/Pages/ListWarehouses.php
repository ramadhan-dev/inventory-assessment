<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use App\Filament\Resources\WarehouseResource\Widgets\WarehouseOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            WarehouseOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}