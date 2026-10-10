<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Exceptions\OrderStatusTransitionException;
use App\Models\Order;
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
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            $currentStatus = $order->status;

            if ($currentStatus === $newStatus) {
                throw new OrderStatusTransitionException(
                    'الطلب موجود بالفعل في هذه الحالة.'
                );
            }

            if (! $currentStatus->canTransitionTo($newStatus)) {
                throw new OrderStatusTransitionException(
                    'لا يمكن تغيير حالة الطلب من '
                        . $currentStatus->value
                        . ' إلى '
                        . $newStatus->value
                        . '.'
                );
            }

            if (
                $newStatus === OrderStatus::Assigned
                && $order->driver_id === null
            ) {
                throw new OrderStatusTransitionException(
                    'يجب تعيين سائق قبل تحويل حالة الطلب إلى assigned.'
                );
            }

            if (
                $newStatus === OrderStatus::PickedUp
                && $order->driver_id === null
            ) {
                throw new OrderStatusTransitionException(
                    'لا يمكن استلام الطلب قبل تعيين سائق له.'
                );
            }

            $order->status = $newStatus;
            $order->save();

            return $order;
        }, attempts: 3);
    }
}
