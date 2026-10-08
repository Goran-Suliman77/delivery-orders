<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Http\Responses\ApiResponse;

class StoreController extends Controller
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

        $query = Store::query()
            ->select([
                'id',
                'name',
                'type',
            ])
            ->withCount([
                'products as products_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ]);

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->string('type')->toString()
            );
        }

        $stores = $query
            ->latest('id')
            ->paginate($perPage);

        return ApiResponse::paginated(
            $stores,
            fn($store) => (new StoreResource($store))
                ->toArray($request),

            'تم جلب المتاجر بنجاح'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        $store->loadCount([
            'products as products_count' => function ($query) {
                $query->where('is_active', true);
            },
        ]);

        return ApiResponse::success(
            message: 'تم جلب المتجر بنجاح',
            data: (new StoreResource($store))
                ->toArray(request())
        );
    }
    public function products(Request $request, Store $store)
    {
        $perPage = min(
            max($request->integer('per_page', 20), 1),
            100
        );

        $products = $store
            ->products()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->where('is_active', true)
            ->latest('id')
            ->paginate($perPage);

         return ApiResponse::paginated(
            $products,
            fn ($product) =>
                (new ProductResource($product))
                    ->toArray($request),
            'تم جلب منتجات المتجر بنجاح'
        );
    }
}
