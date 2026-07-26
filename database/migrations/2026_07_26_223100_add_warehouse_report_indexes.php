<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section D Question 2: Add indexes for complex warehouse report query
     * 
     * This migration adds indexes to optimize the warehouse report query that
     * aggregates data across multiple tables for active warehouses.
     */
    public function up(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            // Index for joining warehouses to product_warehouse
            $table->index('warehouse_id', 'idx_warehouse_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            // Index for finding most recent movement per warehouse
            // This helps with the subquery that gets latest moved product
            $table->index(['warehouse_id', 'created_at'], 'idx_warehouse_created_at_latest');
        });
    }

    public function down(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_warehouse_created_at_latest');
        });
    }
};
