<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Kembali ke Daftar Stock Movements')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => StockMovementResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Movement Details')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Date & Time')
                        ->dateTime('d M Y H:i:s'),
                    TextEntry::make('product.sku')
                        ->label('SKU'),
                    TextEntry::make('product.name')
                        ->label('Product'),
                    TextEntry::make('warehouse.name')
                        ->label('Warehouse'),
                    TextEntry::make('movement_type')
                        ->label('Movement Type')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'in' => 'IN',
                            'out' => 'OUT',
                            'transfer' => 'TRANSFER',
                            'adjustment' => 'ADJUSTMENT',
                            default => strtoupper($state),
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'in' => 'success',
                            'out' => 'danger',
                            'transfer' => 'warning',
                            'adjustment' => 'info',
                            default => 'gray',
                        }),
                    TextEntry::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->color(fn ($state): string => $state > 0 ? 'success' : 'danger'),
                    TextEntry::make('reference_number')
                        ->label('Reference Number'),
                    TextEntry::make('notes')
                        ->label('Notes')
                        ->columnSpanFull(),
                    TextEntry::make('moved_by')
                        ->label('Moved By'),
                ])
                ->columns(2),
        ]);
    }
}
