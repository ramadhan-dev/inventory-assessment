<?php

namespace App\Filament\Resources;

use App\Enums\ProductCategory;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Inventory';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->unique(ignoreRecord: true)
                ->regex('/^[A-Z0-9\-]+$/')
                ->helperText('SKU harus uppercase, alphanumeric, dan dashes. Tidak bisa diubah setelah creation.')
                ->disabled(fn (string $operation): bool => $operation === 'edit'),

            TextInput::make('name')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->rows(3)
                ->nullable(),

            TextInput::make('unit_price')
                ->label('Unit Price')
                ->numeric()
                ->required()
                ->minValue(0) // BR4: Unit price ≥ 0
                ->step(0.01)
                ->prefix('IDR'),

            TextInput::make('weight_kg')
                ->label('Weight (kg)')
                ->numeric()
                ->required()
                ->minValue(0)
                ->step(0.01),

            Select::make('category')
                ->options([
                    ProductCategory::RawMaterial->value => ProductCategory::RawMaterial->label(),
                    ProductCategory::FinishedGoods->value => ProductCategory::FinishedGoods->label(),
                    ProductCategory::Packaging->value => ProductCategory::Packaging->label(),
                    ProductCategory::SparePart->value => ProductCategory::SparePart->label(),
                ])
                ->required()
                ->searchable(),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // eager load count warehouses supaya list tidak N+1 saat menampilkan jumlah warehouse
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('warehouses'))
            // matikan klik-di-mana-saja-pada-row untuk buka View;
            // navigasi ke View sekarang hanya lewat tombol ViewAction eksplisit
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex(),

                TextColumn::make('sku')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->formatStateUsing(fn (ProductCategory $state): string => $state->label())
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('weight_kg')
                    ->label('Weight (kg)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('warehouses_count')
                    ->label('Warehouses')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        ProductCategory::RawMaterial->value => ProductCategory::RawMaterial->label(),
                        ProductCategory::FinishedGoods->value => ProductCategory::FinishedGoods->label(),
                        ProductCategory::Packaging->value => ProductCategory::Packaging->label(),
                        ProductCategory::SparePart->value => ProductCategory::SparePart->label(),
                    ])
                    ->label('Category'),

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
                        // paksa buka di modal, jangan navigasi ke halaman /edit
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
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WarehousesRelationManager::class,
        ];
    }
}
