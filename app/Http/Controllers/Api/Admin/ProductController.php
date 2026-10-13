<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Contracts\ProductManagementServiceInterface;
use App\Contracts\StoreManagementServiceInterface;
use App\Http\Requests\StoreProductIndexRequest;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductManagementServiceInterface $products,
        private readonly StoreManagementServiceInterface $stores,
    ) {}

    public function indexForStore(
        StoreProductIndexRequest $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $store = $this->stores->findForAdministration(
            $storeId
        );

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $perPage = (int) (
            $request->validated()['per_page'] ?? 50
        );

        $products = $this->products->paginateForStore(
            $store,
            $perPage
        );

        $message = $products->total() > 0
            ? 'تم جلب منتجات المتجر بنجاح'
            : 'لا توجد منتجات في هذا المتجر';

        return ApiResponse::paginated(
            $products,
            fn($product) => (new AdminProductResource($product))
                ->toArray($request),
            $message
        );
    }

    public function store(
        ProductRequest $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $store = $this->stores->findForAdministration(
            $storeId
        );

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $product = $this->products->createForStore(
            $store,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'تم إنشاء المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray($request),
            status: 201
        );
    }

    public function show(
        Request $request,
        int $productId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $product = $this->products->findForAdministration(
            $productId
        );

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'تم جلب بيانات المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray($request)
        );
    }

    public function update(
        ProductRequest $request,
        int $productId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $product = $this->products->findForAdministration(
            $productId
        );

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        $product = $this->products->update(
            $product,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'تم تعديل المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray($request)
        );
    }

    public function activate(int $productId): JsonResponse
    {
        return $this->changeActiveState($productId, true);
    }

    public function deactivate(int $productId): JsonResponse
    {
        return $this->changeActiveState($productId, false);
    }

    private function changeActiveState(
        int $productId,
        bool $active
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $product = $this->products->findForAdministration(
            $productId
        );

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        $result = $this->products->setActive(
            $product,
            $active
        );

        $message = match ([$active, $result['changed']]) {
            [true, true] => 'تم تفعيل المنتج بنجاح',
            [true, false] => 'المنتج مفعّل بالفعل',
            [false, true] => 'تم إيقاف المنتج بنجاح',
            [false, false] => 'المنتج موقوف بالفعل',
        };

        return ApiResponse::success(
            message: $message,
            data: (new AdminProductResource($result['product']))
                ->toArray(request())
        );
    }
}
