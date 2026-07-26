<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\StockMovementController;
use App\Http\Controllers\API\WarehouseController;
use App\Http\Controllers\API\StockReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Version 1 API endpoints with Sanctum token authentication
| Rate limit: 60 requests per minute per token
|
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{sku}', [ProductController::class, 'show']);

    // Stock Movements
    Route::post('/stock-movements', [StockMovementController::class, 'store']);

    // Warehouses
    Route::get('/warehouses/{id}/stock', [WarehouseController::class, 'stock']);

    // Stock Report
    Route::get('/stock-report', [StockReportController::class, 'index']);
});
