<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseReportController extends Controller
{
    public function index()
    {
        // Optimized SQL Query using CTEs and window functions
        $results = DB::select("
            WITH warehouse_stock AS (
                -- Calculate stock value per warehouse
                SELECT 
                    pw.warehouse_id,
                    COUNT(DISTINCT pw.product_id) as total_products,
                    SUM(p.unit_price * pw.quantity_on_hand) as total_stock_value
                FROM product_warehouse pw
                INNER JOIN products p ON pw.product_id = p.id
                WHERE pw.quantity_on_hand > 0
                GROUP BY pw.warehouse_id
            ),
            latest_movements AS (
                -- Get most recent movement per warehouse using window function
                SELECT 
                    sm.warehouse_id,
                    sm.product_id,
                    sm.created_at,
                    ROW_NUMBER() OVER (PARTITION BY sm.warehouse_id ORDER BY sm.created_at DESC) as rn
                FROM stock_movements sm
            )
            SELECT 
                w.id,
                w.name,
                w.location,
                COALESCE(ws.total_products, 0) as total_distinct_products,
                COALESCE(ws.total_stock_value, 0) as total_stock_value,
                p.name as most_recently_moved_product,
                lm.created_at as most_recent_movement_date
            FROM warehouses w
            LEFT JOIN warehouse_stock ws ON w.id = ws.warehouse_id
            LEFT JOIN latest_movements lm ON w.id = lm.warehouse_id AND lm.rn = 1
            LEFT JOIN products p ON lm.product_id = p.id
            WHERE w.is_active = true
            ORDER BY w.name;
        ");

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Laravel Eloquent equivalent of the optimized query
     * This provides the same results using Eloquent ORM
     */
    public function eloquent()
    {
        $warehouses = Warehouse::where('is_active', true)
            ->with(['products' => function ($query) {
                $query->wherePivot('quantity_on_hand', '>', 0);
            }])
            ->with(['stockMovements' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get()
            ->map(function ($warehouse) {
                $totalProducts = $warehouse->products->count();
                $totalStockValue = $warehouse->products->sum(function ($product) {
                    return $product->unit_price * $product->pivot->quantity_on_hand;
                });
                
                $latestMovement = $warehouse->stockMovements->first();
                
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'total_distinct_products' => $totalProducts,
                    'total_stock_value' => $totalStockValue,
                    'most_recently_moved_product' => $latestMovement?->product->name,
                    'most_recent_movement_date' => $latestMovement?->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $warehouses
        ]);
    }
}
