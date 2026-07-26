<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\StockMovementStoreRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StockMovementController extends Controller
{
    /**
     * Store a newly created stock movement.
     *
     * @param StockMovementStoreRequest $request
     * @return JsonResponse
     */
    public function store(StockMovementStoreRequest $request): JsonResponse
    {
        // Find product by SKU
        $product = Product::where('sku', $request->product_sku)->firstOrFail();
        
        // Find warehouse
        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        // Create stock movement
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => $request->movement_type,
            'quantity' => $request->quantity,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'moved_by' => $request->moved_by,
        ]);

        // Load relationships for response
        $movement->load(['product', 'warehouse']);

        return response()->json([
            'success' => true,
            'data' => new StockMovementResource($movement),
        ], Response::HTTP_CREATED);
    }
}
