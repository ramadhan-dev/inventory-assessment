<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class WarehouseReportCacheService
{

    /**
     * Get warehouse report data from cache
     */
    public function getCachedReport()
    {
        // Try to get from cache table first
        $cachedData = DB::table('warehouse_report_cache as wrc')
            ->join('warehouses as w', 'wrc.warehouse_id', '=', 'w.id')
            ->where('w.is_active', true)
            ->select([
                'w.id',
                'w.name',
                'w.location',
                'wrc.total_distinct_products',
                'wrc.total_stock_value',
                'wrc.latest_product_name as most_recently_moved_product',
                'wrc.latest_movement_date as most_recent_movement_date',
                'wrc.last_refreshed_at'
            ])
            ->orderBy('w.name')
            ->get();

        // If cache is empty or stale (older than 1 hour), refresh it
        if ($cachedData->isEmpty() || $this->isCacheStale()) {
            $this->refreshCache();
            $cachedData = DB::table('warehouse_report_cache as wrc')
                ->join('warehouses as w', 'wrc.warehouse_id', '=', 'w.id')
                ->where('w.is_active', true)
                ->select([
                    'w.id',
                    'w.name',
                    'w.location',
                    'wrc.total_distinct_products',
                    'wrc.total_stock_value',
                    'wrc.latest_product_name as most_recently_moved_product',
                    'wrc.latest_movement_date as most_recent_movement_date',
                    'wrc.last_refreshed_at'
                ])
                ->orderBy('w.name')
                ->get();
        }

        return $cachedData;
    }

    /**
     * Refresh the cache table with fresh data
     * This is called when cache is stale or on stock movement events
     */
    public function refreshCache()
    {
        DB::statement("
            INSERT INTO warehouse_report_cache (
                warehouse_id,
                total_distinct_products,
                total_stock_value,
                latest_product_id,
                latest_product_name,
                latest_movement_date,
                last_refreshed_at,
                created_at,
                updated_at
            )
            WITH warehouse_stock AS (
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
                SELECT 
                    sm.warehouse_id,
                    sm.product_id,
                    sm.created_at,
                    ROW_NUMBER() OVER (PARTITION BY sm.warehouse_id ORDER BY sm.created_at DESC) as rn
                FROM stock_movements sm
            )
            SELECT 
                w.id as warehouse_id,
                COALESCE(ws.total_products, 0) as total_distinct_products,
                COALESCE(ws.total_stock_value, 0) as total_stock_value,
                lm.product_id as latest_product_id,
                p.name as latest_product_name,
                lm.created_at as latest_movement_date,
                NOW() as last_refreshed_at,
                NOW() as created_at,
                NOW() as updated_at
            FROM warehouses w
            LEFT JOIN warehouse_stock ws ON w.id = ws.warehouse_id
            LEFT JOIN latest_movements lm ON w.id = lm.warehouse_id AND lm.rn = 1
            LEFT JOIN products p ON lm.product_id = p.id
            ON DUPLICATE KEY UPDATE
                total_distinct_products = VALUES(total_distinct_products),
                total_stock_value = VALUES(total_stock_value),
                latest_product_id = VALUES(latest_product_id),
                latest_product_name = VALUES(latest_product_name),
                latest_movement_date = VALUES(latest_movement_date),
                last_refreshed_at = VALUES(last_refreshed_at),
                updated_at = NOW()
        ");
    }

    /**
     * Refresh cache for a specific warehouse only
     * More efficient when only one warehouse's data changes
     */
    public function refreshWarehouseCache($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) return;

        DB::statement("
            INSERT INTO warehouse_report_cache (
                warehouse_id,
                total_distinct_products,
                total_stock_value,
                latest_product_id,
                latest_product_name,
                latest_movement_date,
                last_refreshed_at,
                created_at,
                updated_at
            )
            WITH warehouse_stock AS (
                SELECT 
                    pw.warehouse_id,
                    COUNT(DISTINCT pw.product_id) as total_products,
                    SUM(p.unit_price * pw.quantity_on_hand) as total_stock_value
                FROM product_warehouse pw
                INNER JOIN products p ON pw.product_id = p.id
                WHERE pw.warehouse_id = ? AND pw.quantity_on_hand > 0
                GROUP BY pw.warehouse_id
            ),
            latest_movements AS (
                SELECT 
                    sm.warehouse_id,
                    sm.product_id,
                    sm.created_at,
                    ROW_NUMBER() OVER (PARTITION BY sm.warehouse_id ORDER BY sm.created_at DESC) as rn
                FROM stock_movements sm
                WHERE sm.warehouse_id = ?
            )
            SELECT 
                w.id as warehouse_id,
                COALESCE(ws.total_products, 0) as total_distinct_products,
                COALESCE(ws.total_stock_value, 0) as total_stock_value,
                lm.product_id as latest_product_id,
                p.name as latest_product_name,
                lm.created_at as latest_movement_date,
                NOW() as last_refreshed_at,
                NOW() as created_at,
                NOW() as updated_at
            FROM warehouses w
            LEFT JOIN warehouse_stock ws ON w.id = ws.warehouse_id
            LEFT JOIN latest_movements lm ON w.id = lm.warehouse_id AND lm.rn = 1
            LEFT JOIN products p ON lm.product_id = p.id
            WHERE w.id = ?
            ON DUPLICATE KEY UPDATE
                total_distinct_products = VALUES(total_distinct_products),
                total_stock_value = VALUES(total_stock_value),
                latest_product_id = VALUES(latest_product_id),
                latest_product_name = VALUES(latest_product_name),
                latest_movement_date = VALUES(latest_movement_date),
                last_refreshed_at = VALUES(last_refreshed_at),
                updated_at = NOW()
        ", [$warehouseId, $warehouseId, $warehouseId]);
    }

    /**
     * Check if cache is stale (older than 1 hour)
     */
    private function isCacheStale(): bool
    {
        $lastRefresh = DB::table('warehouse_report_cache')
            ->max('last_refreshed_at');

        if (!$lastRefresh) return true;

        return now()->subHour()->gt($lastRefresh);
    }

    /**
     * Clear all cache data
     */
    public function clearCache()
    {
        DB::table('warehouse_report_cache')->truncate();
    }
}
