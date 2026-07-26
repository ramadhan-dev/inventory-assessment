<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseStockResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'capacity_m3' => (float) $this->capacity_m3,
            'is_active' => $this->is_active,
            'quantity_on_hand' => (int) ($this->pivot->quantity_on_hand ?? 0),
        ];
    }
}
