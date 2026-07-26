<?php

namespace App\Models;

use App\Services\WarehouseReportCacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes (whitelist, bukan $guarded = []).
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'reference_number',
        'notes',
        'moved_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Business rule: BR3 - Transfer qty ≤ available
     * BR5: Movement qty ≠ 0
     */
    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            // BR5: Movement qty ≠ 0
            if ($movement->quantity === 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity tidak boleh 0.',
                ]);
            }

            // BR3: Transfer qty ≤ available (validate source warehouse has sufficient stock)
            if ($movement->movement_type === 'out' || $movement->movement_type === 'transfer') {
                $product = Product::find($movement->product_id);
                $warehouse = Warehouse::find($movement->warehouse_id);

                if ($product && $warehouse) {
                    $currentStock = $product->warehouses()
                        ->where('warehouses.id', $warehouse->id)
                        ->withPivot('quantity_on_hand')
                        ->first()?->pivot->quantity_on_hand ?? 0;

                    if ($currentStock < abs($movement->quantity)) {
                        throw ValidationException::withMessages([
                            'quantity' => "Stok tidak mencukupi. Current stock: {$currentStock}, Requested: " . abs($movement->quantity),
                        ]);
                    }
                }
            }
        });

        static::created(function (StockMovement $movement) {
            // Update product_warehouse pivot table
            $product = Product::find($movement->product_id);
            $warehouse = Warehouse::find($movement->warehouse_id);

            if ($product && $warehouse) {
                $pivot = $product->warehouses()
                    ->where('warehouses.id', $warehouse->id)
                    ->withPivot('quantity_on_hand')
                    ->first();

                if ($pivot) {
                    // Update existing pivot
                    $newQuantity = $pivot->pivot->quantity_on_hand + $movement->quantity;
                    $pivot->pivot->update([
                        'quantity_on_hand' => max(0, $newQuantity),
                    ]);
                } else {
                    // Create new pivot relationship
                    $product->warehouses()->attach($warehouse->id, [
                        'quantity_on_hand' => max(0, $movement->quantity),
                    ]);
                }

                // Section D Question 3: Refresh cache for affected warehouse
                $cacheService = App::make(WarehouseReportCacheService::class);
                $cacheService->refreshWarehouseCache($warehouse->id);
            }
        });
    }

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    public function scopeMovementType(Builder $query, string $type): Builder
    {
        return $query->where('movement_type', $type);
    }

    public function scopeDateRange(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeInbound(Builder $query): Builder
    {
        return $query->where('movement_type', 'in')->where('quantity', '>', 0);
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->where('movement_type', 'out')->where('quantity', '<', 0);
    }
}
