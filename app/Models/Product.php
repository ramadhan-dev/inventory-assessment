<?php

namespace App\Models;

use App\Enums\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit_price',
        'weight_kg',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'weight_kg' => 'decimal:2',
            'category' => ProductCategory::class,
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouse')
            ->withPivot('quantity_on_hand')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /*
    | Scopes
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query, ProductCategory|string $category): Builder
    {
        $value = $category instanceof ProductCategory ? $category->value : $category;

        return $query->where('category', $value);
    }

    /*
    */
    protected function sku(): Attribute
    {
        return Attribute::make(
            set: function (string $value) {
                if ($this->exists) {
                    return $this->getOriginal('sku');
                }

                return strtoupper($value);
            },
        );
    }

    /*
    | Helpers
    */

    public function totalQuantityOnHand(): int
    {
        return (int) $this->warehouses()->sum('product_warehouse.quantity_on_hand');
    }
}