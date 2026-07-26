<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehousesRelationManager extends RelationManager
{
    protected static string $relationship = 'warehouses';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('quantity_on_hand')
                ->label('Quantity on Hand')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('capacity_m3')
                    ->label('Capacity (m³)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('pivot.quantity_on_hand')
                    ->label('Quantity on Hand')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                Action::make('updateQuantity')
                    ->label('Update Qty')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        TextInput::make('quantity_on_hand')
                            ->label('Quantity on Hand')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            // pre-isi form dengan nilai pivot saat ini
                            ->default(fn ($record) => $record->pivot->quantity_on_hand),
                    ])
                    // munculkan popup "Are you sure?" setelah form disubmit,
                    // sebelum benar-benar menyimpan ke database
                    ->requiresConfirmation()
                    ->modalHeading('Update Stock Quantity')
                    ->modalDescription(fn ($record) => "Yakin mau ubah quantity untuk {$record->name}?")
                    ->action(function ($record, array $data) {
                        $record->pivot->update([
                            'quantity_on_hand' => $data['quantity_on_hand'],
                        ]);

                        Notification::make()
                            ->title('Quantity berhasil diupdate')
                            ->success()
                            ->send();
                    }),

                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10);
    }
}
