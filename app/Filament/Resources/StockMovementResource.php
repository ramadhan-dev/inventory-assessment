<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->label('Product')
                ->relationship('product', 'sku')
                ->searchable()
                ->getOptionLabelFromRecordUsing(fn (Product $record) => "{$record->sku} - {$record->name}")
                ->required(),

            Select::make('warehouse_id')
                ->label('Warehouse')
                ->relationship('warehouse', 'name')
                ->searchable()
                ->required(),

            Select::make('movement_type')
                ->label('Movement Type')
                ->options([
                    'in' => 'In (Stock In)',
                    'out' => 'Out (Stock Out)',
                    'transfer' => 'Transfer',
                    'adjustment' => 'Adjustment',
                ])
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Auto-set quantity sign based on movement type
                    if ($state === 'in') {
                        // Positive for in
                    } elseif ($state === 'out') {
                        // Negative for out
                    }
                }),

            TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->required()
                ->minValue(-999999)
                ->maxValue(999999)
                ->rule('not_in:0') // BR5: Movement qty ≠ 0
                ->helperText('Positive for IN, negative for OUT/TRANSFER. Cannot be zero.')
                ->default(1),

            TextInput::make('reference_number')
                ->label('Reference Number')
                ->maxLength(100)
                ->nullable(),

            Textarea::make('notes')
                ->rows(3)
                ->nullable(),

            TextInput::make('moved_by')
                ->label('Moved By')
                ->maxLength(255)
                ->default(auth()->user()->name ?? 'System')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // eager load product dan warehouse supaya list tidak N+1
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product', 'warehouse']))
            // matikan klik-di-mana-saja-pada-row untuk buka View;
            // navigasi ke View sekarang hanya lewat tombol ViewAction eksplisit
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('product.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('movement_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        'transfer' => 'warning',
                        'adjustment' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in' => 'IN',
                        'out' => 'OUT',
                        'transfer' => 'TRANSFER',
                        'adjustment' => 'ADJUST',
                        default => strtoupper($state),
                    })
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('moved_by')
                    ->label('Moved By')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('movement_type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                        'transfer' => 'Transfer',
                        'adjustment' => 'Adjustment',
                    ])
                    ->label('Movement Type'),

                SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'sku')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),

                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label('Actions')
                    ->tooltip('Actions')
                    ->color('gray'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(\App\Filament\Exports\StockMovementExporter::class)
                    ->label('Export CSV'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'view' => Pages\ViewStockMovement::route('/{record}'),
        ];
    }
}
