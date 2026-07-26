<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWarehouse extends ViewRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Kembali ke Daftar Warehouse')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => WarehouseResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Warehouse Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('location'),
                    TextEntry::make('capacity_m3')
                        ->label('Capacity (m³)'),
                    TextEntry::make('is_active')
                        ->badge()
                        ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                        ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                ])
                ->columns(2),
        ]);
    }

    // Relation manager "Products in this Warehouse" (dengan tabel + pagination
    // penuh) otomatis muncul di bawah infolist ini, karena sudah didaftarkan
    // di WarehouseResource::getRelations().
}