<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\WarehouseStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    /**
     * Display all products with quantities in a warehouse.
     *
     * @param int $id
     * @param WarehouseStockRequest $request
     * @return JsonResponse
     */
    public function stock(int $id, WarehouseStockRequest $request): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        // Get all products with their stock in this warehouse
        $products = Product::query()
            ->whereHas('warehouses', function ($query) use ($id) {
                $query->where('warehouses.id', $id);
            })
            ->with(['warehouses' => function ($query) use ($id) {
                $query->where('warehouses.id', $id);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'capacity_m3' => (float) $warehouse->capacity_m3,
                    'is_active' => $warehouse->is_active,
                ],
                'products' => ProductResource::collection($products),
            ],
        ]);
    }
}
