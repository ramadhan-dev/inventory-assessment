<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

class StockReportController extends Controller
{
    /**
     * Display aggregated stock value per warehouse.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
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
        ]);
    }
}
