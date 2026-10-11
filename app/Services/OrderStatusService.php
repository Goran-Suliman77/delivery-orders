<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderStatusTransitionException;
use App\Models\Order;
use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    public function change(
        int $orderId,
        OrderStatus $newStatus
    ): Order {
        return DB::transaction(function () use (
            $orderId,
            $newStatus
        ) {
            // نقفل الطلب حتى لا تعدّله عملية أخرى بالتزامن.
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            $currentStatus = $order->status;

            if ($currentStatus === $newStatus) {
                throw new OrderStatusTransitionException(
                    'الطلب موجود بالفعل في هذه الحالة'
                );
            }

            if (! $currentStatus->canTransitionTo($newStatus)) {
                throw new OrderStatusTransitionException(
                    'لا يمكن الانتقال من الحالة الحالية إلى الحالة المطلوبة'
                );
            }

            // لا يمكن أن يصبح الطلب مسندًا أو مستلمًا
            // دون وجود سائق مرتبط به.
            if (
                in_array(
                    $newStatus,
                    [
                        OrderStatus::Assigned,
                        OrderStatus::PickedUp,
                    ],
                    true
                )
                && $order->driver_id === null
            ) {
                throw new OrderStatusTransitionException(
                    'لا يمكن تغيير الحالة دون تعيين سائق'
                );
            }

            $driverId = $order->driver_id;

            // نحفظ الحالة الجديدة أولًا داخل المعاملة.
            $order->status = $newStatus;
            $order->save();

            $isFinished = in_array(
                $newStatus,
                [
                    OrderStatus::Delivered,
                    OrderStatus::Cancelled,
                ],
                true
            );

            if ($isFinished && $driverId !== null) {
                // نقفل سجل السائق أيضًا.
                $driver = Driver::query()
                    ->lockForUpdate()
                    ->find($driverId);

                if ($driver) {
                    // active() يستثني delivered وcancelled،
                    // لذلك يفحص هذا الاستعلام الطلبات المتبقية.
                    $hasActiveOrders = Order::query()
                        ->active()
                        ->where('driver_id', $driver->id)
                        ->exists();

                    $driver->is_available = ! $hasActiveOrders;
                    $driver->save();
                }
            }

            return $order;
        }, attempts: 5);
    }
}
