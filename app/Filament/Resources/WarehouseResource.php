<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Filament\Resources\WarehouseResource\RelationManagers;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->minLength(3)
                ->maxLength(100),

            TextInput::make('location')
                ->required()
                ->maxLength(255),

            TextInput::make('capacity_m3')
                ->label('Capacity (m³)')
                ->numeric()
                ->required()
                ->minValue(0.01) // capacity: numeric > 0
                ->step(0.01),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Tidak bisa dinonaktifkan selama warehouse masih memiliki stok.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // eager load count produk supaya list tidak N+1 saat menampilkan jumlah produk
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('products'))
            // matikan klik-di-mana-saja-pada-row untuk buka View;
            // navigasi ke View sekarang hanya lewat tombol ViewAction eksplisit
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->searchable(),

                TextColumn::make('capacity_m3')
                    ->label('Capacity (m³)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('products_count')
                    ->label('Products'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active status'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        // paksa buka di modal, jangan navigasi ke halaman /edit,
                        // walau route 'edit' masih terdaftar (tetap bisa diakses
                        // langsung lewat URL kalau dibutuhkan)
                        ->modal()
                        ->url(null),

                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->label('Actions')
                    ->tooltip('Actions')
                    ->color('gray'),
            ])
            ->defaultSort('capacity_m3', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
            'view' => Pages\ViewWarehouse::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
        ];
    }
}