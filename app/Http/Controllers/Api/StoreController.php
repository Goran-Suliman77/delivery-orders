<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Http\Responses\ApiResponse;
use App\Contracts\ProductQueryServiceInterface;
use App\Contracts\StoreQueryServiceInterface;
use App\Http\Requests\StoreIndexRequest;
use App\Http\Requests\StoreProductIndexRequest;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{

    public function __construct(
        private readonly StoreQueryServiceInterface $storeQueries,
        private readonly ProductQueryServiceInterface $productQueries,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(
        StoreIndexRequest $request
    ): JsonResponse {
        $stores = $this->storeQueries->paginatePublic(
            $request->validated()
        );

        $message = $stores->total() > 0
            ? 'تم جلب المتاجر بنجاح'
            : 'لا توجد متاجر تطابق معايير البحث';

        if ($stores->isEmpty() && $stores->total() > 0) {
            $message = 'لا توجد متاجر في هذه الصفحة. جرّب صفحة أخرى.';
        }

        return ApiResponse::paginated(
            $stores,
            fn($store) => (new StoreResource($store))
                ->toArray($request),
            $message
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(
        Request $request,
        int $store
    ): JsonResponse {
        $storeModel = $this->storeQueries->findPublicById(
            $store
        );

        if (! $storeModel) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود أو غير متاح',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'تم جلب بيانات المتجر بنجاح',
            data: (new StoreResource($storeModel))
                ->toArray($request)
        );
    }
    public function products(
        StoreProductIndexRequest $request,
        int $store
    ): JsonResponse {
        $storeModel = $this->storeQueries->findPublicById(
            $store
        );

        if (! $storeModel) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود أو غير متاح',
                status: 404
            );
        }

        $perPage = (int) (
            $request->validated()['per_page'] ?? 20
        );

        $products = $this->productQueries->paginateForStore(
            $storeModel,
            $perPage
        );

        $message = $products->total() > 0
            ? 'تم جلب منتجات المتجر بنجاح'
            : 'لا توجد منتجات متاحة في هذا المتجر';

        return ApiResponse::paginated(
            $products,
            fn($product) => (new ProductResource($product))
                ->toArray($request),
            $message
        );
    }
}
