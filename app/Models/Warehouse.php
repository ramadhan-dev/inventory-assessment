<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes (whitelist, bukan $guarded = []).
     */
    protected $fillable = [
        'name',
        'location',
        'capacity_m3',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity_m3' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     */
    protected static function booted(): void
    {
        static::updating(function (Warehouse $warehouse) {
            $isBeingDeactivated = $warehouse->isDirty('is_active')
                && $warehouse->getOriginal('is_active') === true
                && $warehouse->is_active === false;

            if ($isBeingDeactivated && $warehouse->hasStock()) {
                throw ValidationException::withMessages([
                    'is_active' => 'Warehouse ini masih memiliki produk dengan stok (quantity_on_hand > 0), tidak bisa dinonaktifkan.',
                ]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_warehouse')
            ->withPivot('quantity_on_hand')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------
    | Business rule helpers
    |--------------------------------------------------------------------
    */

    /**
     */
    public function hasStock(): bool
    {
        return $this->products()
            ->wherePivot('quantity_on_hand', '>', 0)
            ->exists();
    }

    /**
     */
    public function capacityUtilizationPercent(): float
    {
        $used = (float) $this->products()->sum('product_warehouse.quantity_on_hand');
        $capacity = (float) $this->capacity_m3;

        return $capacity > 0
            ? round(($used / $capacity) * 100, 2)
            : 0.0;
    }
}