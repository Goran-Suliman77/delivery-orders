<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreRequest;
use App\Http\Resources\AdminStoreResource;
use App\Http\Responses\ApiResponse;
use App\Models\Store;
use App\Models\Scopes\ActiveStoreScope;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    //
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage', Store::class);

        $perPage = min(
            max((int) $request->query('per_page', 50), 1),
            100
        );

        $query = Store::withoutGlobalScope(ActiveStoreScope::class)
            ->select([
                'id',
                'name',
                'type',
                'is_active',
            ])
            ->withCount('products');

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $stores = $query
            ->latest('id')
            ->paginate($perPage);

        $message = $stores->total() > 0
            ? 'تم جلب المتاجر بنجاح'
            : 'لا توجد متاجر تطابق معايير البحث';

        return ApiResponse::paginated(
            $stores,
            fn($store) => (new AdminStoreResource($store))
                ->toArray($request),
            $message
        );
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $this->authorize('manage', Store::class);

        $store = Store::create([
            'name' => $request->validated('name'),
            'type' => $request->validated('type'),
            'is_active' => true,
        ]);

        $store->loadCount('products');

        return ApiResponse::success(
            message: 'تم إنشاء المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray($request),
            status: 201
        );
    }

    public function show(int $storeId): JsonResponse
    {
        $this->authorize('manage', Store::class);

        $store = $this->findStore($storeId);

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'تم جلب بيانات المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray(request())
        );
    }

    public function update(
        StoreRequest $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $store = $this->findStore($storeId);

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $store->fill($request->validated());
        $store->save();
        $store->loadCount('products');

        return ApiResponse::success(
            message: 'تم تعديل بيانات المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray($request)
        );
    }

    public function activate(int $storeId): JsonResponse
    {
        $this->authorize('manage', Store::class);

        return $this->setActive($storeId, true);
    }

    public function deactivate(int $storeId): JsonResponse
    {
        $this->authorize('manage', Store::class);

        return $this->setActive($storeId, false);
    }

    private function setActive(
        int $storeId,
        bool $active
    ): JsonResponse {
        $store = $this->findStore($storeId);

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $alreadyInState = (bool) $store->is_active === $active;

        if (! $alreadyInState) {
            $store->is_active = $active;
            $store->save();
        }

        $store->loadCount('products');

        if ($alreadyInState) {
            $message = $active
                ? 'المتجر مفعّل بالفعل'
                : 'المتجر موقوف بالفعل';
        } else {
            $message = $active
                ? 'تم تفعيل المتجر بنجاح'
                : 'تم إيقاف المتجر بنجاح';
        }

        return ApiResponse::success(
            message: $message,
            data: (new AdminStoreResource($store))
                ->toArray(request())
        );
    }

    private function findStore(int $storeId): ?Store
    {
        return Store::withoutGlobalScope(ActiveStoreScope::class)
            ->select([
                'id',
                'name',
                'type',
                'is_active',
            ])
            ->withCount('products')
            ->find($storeId);
    }
}
