<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Http\Responses\ApiResponse;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = min(
            max($request->integer('per_page', 20), 1),
            100
        );

        $query = Product::query()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->where('is_active', true)
            ->whereHas('store')
            ->with('store:id,name');

        if ($request->filled('store_id')) {
            $query->where(
                'store_id',
                $request->integer('store_id')
            );
        }

        $products = $query
            ->latest('id')
            ->paginate($perPage);

        $data = collect($products->items())
            ->map(
                fn($product) => (new ProductResource($product))
                    ->toArray($request)
            )
            ->values();

        return ApiResponse::success(
            message: 'تم جلب المنتجات بنجاح',
            data: $data,
            extra: [
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
            ]
        );
    }



    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product = Product::query()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->where('is_active', true)
            ->whereHas('store')
            ->with('store:id,name')
            ->findOrFail($product);

        return ApiResponse::success(
            message: 'تم جلب المنتج بنجاح',
            data: (new ProductResource($product))
                ->toArray(request())
        );
    }
}
