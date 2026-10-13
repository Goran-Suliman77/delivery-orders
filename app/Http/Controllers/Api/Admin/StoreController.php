<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\StoreRequest;
use App\Http\Resources\AdminStoreResource;
use App\Http\Responses\ApiResponse;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Contracts\StoreManagementServiceInterface;
use App\Http\Requests\StoreIndexRequest;

class StoreController extends Controller
{
    //
    public function __construct(
        private readonly StoreManagementServiceInterface $stores
    ) {}

    public function index(
        StoreIndexRequest $request
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $stores = $this->stores->paginateForAdministration(
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
            fn($store) => (new AdminStoreResource($store))
                ->toArray($request),
            $message
        );
    }

    public function store(
        StoreRequest $request
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $store = $this->stores->create(
            $request->validated()
        );

        return ApiResponse::success(
            message: 'تم إنشاء المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray($request),
            status: 201
        );
    }

    public function show(
        Request $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $store = $this->stores->findForAdministration(
            $storeId
        );

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        return ApiResponse::success(
            message: 'تم جلب بيانات المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray($request)
        );
    }

    public function update(
        StoreRequest $request,
        int $storeId
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $store = $this->stores->findForAdministration(
            $storeId
        );

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $store = $this->stores->update(
            $store,
            $request->validated()
        );

        return ApiResponse::success(
            message: 'تم تعديل بيانات المتجر بنجاح',
            data: (new AdminStoreResource($store))
                ->toArray($request)
        );
    }

    public function activate(int $storeId): JsonResponse
    {
        return $this->changeActiveState($storeId, true);
    }

    public function deactivate(int $storeId): JsonResponse
    {
        return $this->changeActiveState($storeId, false);
    }

    private function changeActiveState(
        int $storeId,
        bool $active
    ): JsonResponse {
        $this->authorize('manage', Store::class);

        $store = $this->stores->findForAdministration(
            $storeId
        );

        if (! $store) {
            return ApiResponse::error(
                message: 'المتجر المطلوب غير موجود',
                status: 404
            );
        }

        $result = $this->stores->setActive(
            $store,
            $active
        );

        $message = match ([$active, $result['changed']]) {
            [true, true] => 'تم تفعيل المتجر بنجاح',
            [true, false] => 'المتجر مفعّل بالفعل',
            [false, true] => 'تم إيقاف المتجر بنجاح',
            [false, false] => 'المتجر موقوف بالفعل',
        };

        return ApiResponse::success(
            message: $message,
            data: (new AdminStoreResource($result['store']))
                ->toArray(request())
        );
    }
}
