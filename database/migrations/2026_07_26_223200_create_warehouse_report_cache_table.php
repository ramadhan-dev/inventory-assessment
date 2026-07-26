<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section D Question 3: Create cache table for warehouse report optimization
     * 
     * This table stores pre-aggregated warehouse report data to avoid
     * expensive queries on 1.2M stock movements at runtime.
     * Cache is refreshed via Laravel events when stock movements change.
     */
    public function up(): void
    {
        Schema::create('warehouse_report_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_distinct_products')->default(0);
            $table->decimal('total_stock_value', 15, 2)->default(0);
            $table->foreignId('latest_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('latest_product_name')->nullable();
            $table->timestamp('latest_movement_date')->nullable();
            $table->timestamp('last_refreshed_at')->useCurrent();
            $table->timestamps();

            $table->unique('warehouse_id');
            $table->index('last_refreshed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_report_cache');
    }
};
