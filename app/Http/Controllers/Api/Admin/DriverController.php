<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverRequest;
use App\Http\Resources\AdminDriverResource;
use App\Http\Responses\ApiResponse;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Driver::class);

        $perPage = min(
            max((int) $request->query('per_page', 50), 1),
            100
        );

        $query = Driver::query()
            ->select([
                'id',
                'user_id',
                'vehicle_type',
                'vehicle_number',
                'is_available',
                'created_at',
            ])
            ->with('user:id,name,email')
            ->withCount([
                'orders as active_orders_count' =>
                fn($query) => $query->active(),
            ]);

        // فلترة اختيارية حسب التوفر: true أو false.
        if ($request->has('is_available')) {
            $value = strtolower(
                (string) $request->query('is_available')
            );

            if (in_array(
                $value,
                ['1', '0', 'true', 'false'],
                true
            )) {
                $isAvailable = filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN
                );

                $query->where('is_available', $isAvailable);
            }
        }

        $drivers = $query
            ->latest('id')
            ->paginate($perPage);

        $message = $drivers->total() > 0
            ? 'تم جلب السائقين بنجاح'
            : 'لا يوجد سائقون يطابقون معايير البحث';

        return ApiResponse::paginated(
            $drivers,
            fn($driver) => (new AdminDriverResource($driver))
                ->toArray($request),
            $message
        );
    }

    public function show(int $driverId): JsonResponse
    {
        $driver = $this->findDriver($driverId);

        if (! $driver) {
            return ApiResponse::error(
                message: 'السائق المطلوب غير موجود',
                status: 404
            );
        }

        $this->authorize('view', $driver);

        return ApiResponse::success(
            message: 'تم جلب بيانات السائق بنجاح',
            data: (new AdminDriverResource($driver))
                ->toArray(request())
        );
    }

    public function update(
        DriverRequest $request,
        int $driverId
    ): JsonResponse {
        $driver = $this->findDriver($driverId);

        if (! $driver) {
            return ApiResponse::error(
                message: 'السائق المطلوب غير موجود',
                status: 404
            );
        }

        $this->authorize('update', $driver);

        $data = $request->validated();

        if (empty($data)) {
            return ApiResponse::error(
                message: 'أرسل بيانات واحدة على الأقل لتعديل السائق',
                status: 422
            );
        }

        $driver->fill($data);
        $driver->save();

        // نعيد تحميل العلاقات والحسابات التي يعرضها المورد.
        $driver = $this->findDriver($driverId);

        return ApiResponse::success(
            message: 'تم تعديل بيانات السائق بنجاح',
            data: (new AdminDriverResource($driver))
                ->toArray($request)
        );
    }

    private function findDriver(int $driverId): ?Driver
    {
        return Driver::query()
            ->select([
                'id',
                'user_id',
                'vehicle_type',
                'vehicle_number',
                'is_available',
                'created_at',
            ])
            ->with('user:id,name,email')
            ->withCount([
                'orders as active_orders_count' =>
                fn($query) => $query->active(),
            ])
            ->find($driverId);
    }
}
