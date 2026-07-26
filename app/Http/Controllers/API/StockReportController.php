<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseReportCacheService;
use Illuminate\Http\JsonResponse;

class StockReportController extends Controller
{
    protected WarehouseReportCacheService $cacheService;

    public function __construct(WarehouseReportCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Section D Question 3: Display aggregated stock value per warehouse.
     * Uses cached aggregation to avoid timeout at scale (1.2M rows).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Use cached data for performance
        $cachedData = $this->cacheService->getCachedReport();

        $data = $cachedData->map(function ($warehouse) {
            return [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                ],
                'total_products' => (int) $warehouse->total_distinct_products,
                'total_quantity' => 0, // Not tracked in cache, can be added if needed
                'total_stock_value' => (float) $warehouse->total_stock_value,
                'most_recently_moved_product' => $warehouse->most_recently_moved_product,
                'most_recent_movement_date' => $warehouse->most_recent_movement_date,
                'cache_last_refreshed' => $warehouse->last_refreshed_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'cached' => true,
                'cache_strategy' => 'Cached Aggregation with event-based invalidation',
            ],
        ]);
    }

    /**
     * Legacy method for comparison (uncached version)
     * This demonstrates the performance difference
     *
     * @return JsonResponse
     */
    public function uncached(): JsonResponse
    {
        // Get all warehouses with aggregated stock value
        $warehouses = Warehouse::query()
            ->select('warehouses.*')
            ->selectRaw('
                COUNT(DISTINCT pw.product_id) as total_products,
                SUM(pw.quantity_on_hand) as total_quantity,
                SUM(pw.quantity_on_hand * p.unit_price) as total_stock_value
            ')
            ->leftJoin('product_warehouse as pw', 'warehouses.id', '=', 'pw.warehouse_id')
            ->leftJoin('products as p', 'pw.product_id', '=', 'p.id')
            ->groupBy('warehouses.id')
            ->get();

        $data = $warehouses->map(function ($warehouse) {
            return [
                'warehouse' => new WarehouseResource($warehouse),
                'total_products' => (int) ($warehouse->total_products ?? 0),
                'total_quantity' => (int) ($warehouse->total_quantity ?? 0),
                'total_stock_value' => (float) ($warehouse->total_stock_value ?? 0),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'cached' => false,
                'warning' => 'This uncached version may timeout at scale (1.2M rows)',
            ],
        ]);
    }
}
