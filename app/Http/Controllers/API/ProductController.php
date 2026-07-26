<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ProductIndexRequest;
use App\Http\Requests\API\ProductShowRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of products.
     *
     * @param ProductIndexRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(ProductIndexRequest $request): AnonymousResourceCollection
    {
        $query = Product::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by is_active
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by SKU or name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Eager load warehouses for stock information
        $query->with('warehouses');

        // Pagination
        $perPage = $request->input('per_page', 15);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display a single product with warehouse stock levels.
     *
     * @param string $sku
     * @param ProductShowRequest $request
     * @return JsonResponse
     */
    public function show(string $sku, ProductShowRequest $request): JsonResponse
    {
        $product = Product::where('sku', $sku)
            ->with('warehouses')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }
}
