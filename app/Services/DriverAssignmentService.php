<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\DriverAssignmentException;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DriverAssignmentService
{
    public function assign(
        int $orderId,
        int $driverId
    ): Order {
        return DB::transaction(function () use (
            $orderId,
            $driverId
        ) {
            /*
             * قفل الطلب حتى لا تتم عملية تعيين متزامنة
             * على الطلب نفسه.
             */
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            /*
             * يجب أن يكون الطلب جاهزًا للاستلام.
             */
            if ($order->status !== OrderStatus::Ready) {
                throw new DriverAssignmentException(
                    'لا يمكن تعيين سائق إلا للطلب الجاهز للاستلام.'
                );
            }

            /*
             * الطلب الجاهز يجب ألا يكون مرتبطًا
             * بسائق مسبقًا.
             */
            if ($order->driver_id !== null) {
                throw new DriverAssignmentException(
                    'تم تعيين سائق لهذا الطلب مسبقًا.'
                );
            }

            /*
             * قفل السائق حتى لا يُعيّن إلى طلبين
             * متزامنين قبل تحديث إتاحته.
             */
            $driver = Driver::query()
                ->lockForUpdate()
                ->findOrFail($driverId);

            if (! $driver->is_available) {
                throw new DriverAssignmentException(
                    'السائق غير متاح حاليًا.'
                );
            }

            /*
             * فحص إضافي للتأكد من عدم وجود طلب فعّال
             * آخر مسند إلى السائق.
             */
            $hasActiveOrder = Order::query()
                ->where('driver_id', $driver->id)
                ->whereNotIn('status', [
                    OrderStatus::Delivered->value,
                    OrderStatus::Cancelled->value,
                ])
                ->exists();

            if ($hasActiveOrder) {
                throw new DriverAssignmentException(
                    'السائق لديه طلب فعّال بالفعل.'
                );
            }

            /*
             * تحقق إضافي من قانونية الانتقال.
             */
            if (! $order->status->canTransitionTo(
                OrderStatus::Assigned
            )) {
                throw new DriverAssignmentException(
                    'انتقال حالة الطلب إلى assigned غير مسموح.'
                );
            }

            /*
             * تحديث الطلب والسائق داخل Transaction واحدة.
             */
            $order->driver_id = $driver->id;
            $order->status = OrderStatus::Assigned;
            $order->save();

            $driver->is_available = false;
            $driver->save();

            return $order;
        }, attempts: 3);
    }
}
