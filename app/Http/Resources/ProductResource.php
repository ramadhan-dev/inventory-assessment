<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'unit_price' => (float) $this->unit_price,
            'weight_kg' => (float) $this->weight_kg,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'is_active' => $this->is_active,
            'warehouses' => WarehouseStockResource::collection($this->whenLoaded('warehouses')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
