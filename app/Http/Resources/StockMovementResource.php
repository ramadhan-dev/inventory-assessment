<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'product' => [
                'id' => $this->product->id,
                'sku' => $this->product->sku,
                'name' => $this->product->name,
            ],
            'warehouse' => [
                'id' => $this->warehouse->id,
                'name' => $this->warehouse->name,
            ],
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'moved_by' => $this->moved_by,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
