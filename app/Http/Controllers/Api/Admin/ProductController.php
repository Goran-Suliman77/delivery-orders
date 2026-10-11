<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Product;
use App\Models\Store;
use App\Models\Scopes\ActiveStoreScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    public function indexForStore(
        Request $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $store = $this->findStore($storeId);

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $perPage = min(
            max((int) $request->query('per_page', 50), 1),
            100
        );

        $products = $store->products()
            ->select([
                'products.id',
                'products.store_id',
                'products.name',
                'products.price',
                'products.is_active',
            ])
            ->latest('products.id')
            ->paginate($perPage);

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

        $store = $this->findStore($storeId);

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $product = $store->products()->create([
            'name' => $request->validated('name'),
            'price' => $request->validated('price'),
            'is_active' => true,
        ]);

        return ApiResponse::success(
            message: 'تم إنشاء المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray($request),
            status: 201
        );
    }

    public function show(int $productId): JsonResponse
    {
        $this->authorize('manage', Product::class);

        $product = $this->findProduct($productId);

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'تم جلب بيانات المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray(request())
        );
    }

    public function update(
        ProductRequest $request,
        int $productId
    ): JsonResponse {
        $this->authorize('manage', Product::class);

        $product = $this->findProduct($productId);

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        $product->fill($request->validated());
        $product->save();

        return ApiResponse::success(
            message: 'تم تعديل المنتج بنجاح',
            data: (new AdminProductResource($product))
                ->toArray($request)
        );
    }

    public function activate(int $productId): JsonResponse
    {
        $this->authorize('manage', Product::class);

        return $this->setActive($productId, true);
    }

    public function deactivate(int $productId): JsonResponse
    {
        $this->authorize('manage', Product::class);

        return $this->setActive($productId, false);
    }

    private function setActive(
        int $productId,
        bool $active
    ): JsonResponse {
        $product = $this->findProduct($productId);

        if (! $product) {
            return ApiResponse::error(
                message: 'المنتج المطلوب غير موجود',
                status: 404
            );
        }

        $alreadyInState = (bool) $product->is_active === $active;

        if (! $alreadyInState) {
            $product->is_active = $active;
            $product->save();
        }

        if ($alreadyInState) {
            $message = $active
                ? 'المنتج مفعّل بالفعل'
                : 'المنتج موقوف بالفعل';
        } else {
            $message = $active
                ? 'تم تفعيل المنتج بنجاح'
                : 'تم إيقاف المنتج بنجاح';
        }

        return ApiResponse::success(
            message: $message,
            data: (new AdminProductResource($product))
                ->toArray(request())
        );
    }

    private function findStore(int $storeId): ?Store
    {
        return Store::withoutGlobalScope(ActiveStoreScope::class)
            ->find($storeId);
    }

    private function findProduct(int $productId): ?Product
    {
        return Product::query()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
                'is_active',
            ])
            ->find($productId);
    }
}
