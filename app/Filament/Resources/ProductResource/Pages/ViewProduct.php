<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\ProductCategory;
use App\Filament\Resources\ProductResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Kembali ke Daftar Produk')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => ProductResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('sku')
                        ->label('SKU')
                        ->copyable(),
                    TextEntry::make('name'),
                    TextEntry::make('description')
                        ->columnSpanFull(),
                    TextEntry::make('category')
                        ->formatStateUsing(fn (ProductCategory $state): string => $state->label()),
                    TextEntry::make('unit_price')
                        ->label('Unit Price')
                        ->money('IDR'),
                    TextEntry::make('weight_kg')
                        ->label('Weight (kg)')
                        ->numeric(decimalPlaces: 2),
                    TextEntry::make('is_active')
                        ->badge()
                        ->formatStateUsing(fn (bool $state) => $state ? 'Active' : 'Inactive')
                        ->color(fn (bool $state) => $state ? 'success' : 'danger'),
                ])
                ->columns(2),
        ]);
    }

    // Relation manager "Warehouses for this Product" (dengan tabel + pagination
    // penuh) otomatis muncul di bawah infolist ini, karena sudah didaftarkan
    // di ProductResource::getRelations().
}
