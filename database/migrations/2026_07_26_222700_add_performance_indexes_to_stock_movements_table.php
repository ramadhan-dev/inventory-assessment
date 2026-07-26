<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section D: Database Performance at Scale - Index Design
     * 
     * This migration adds optimized indexes for the three query patterns
     * identified in Section D of the assessment requirements.
     */
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Pattern 1: Warehouse + date range (dashboard widget)
            // Query: WHERE warehouse_id = ? AND created_at BETWEEN ? AND ?
            // This composite index allows efficient filtering on both columns
            $table->index(['warehouse_id', 'created_at'], 'idx_warehouse_created_at');

            // Pattern 2: Product aggregate (stock report)
            // Query: WHERE product_id = ? AND movement_type = ?
            // This composite index optimizes filtering and aggregation
            $table->index(['product_id', 'movement_type'], 'idx_product_movement_type');

            // Pattern 3: Reference lookup (audit trail)
            // Query: WHERE reference_number = ?
            // This index speeds up exact lookups by reference number
            $table->index('reference_number', 'idx_reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_created_at');
            $table->dropIndex('idx_product_movement_type');
            $table->dropIndex('idx_reference_number');
        });
    }
};
