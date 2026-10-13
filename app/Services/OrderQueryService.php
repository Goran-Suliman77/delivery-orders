<?php

namespace App\Services;

use App\Contracts\OrderQueryServiceInterface;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderQueryService implements OrderQueryServiceInterface
{
    public function paginateVisibleTo(
        User $user,
        array $filters
    ): LengthAwarePaginator {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 50), 1),
            100
        );

        return Order::query()
            ->active()
            ->visibleTo($user)

            ->select([
                'id',
                'customer_id',
                'store_id',
                'driver_id',
                'status',
                'created_at',
            ])

            ->with([
                'customer:id,name',
                'store:id,name',
                'driver:id,user_id',
                'driver.user:id,name',
            ])

            ->withCount('items')

            ->when(
                $filters['status'] ?? null,
                fn($query, $status) => $query->status($status)
            )

            ->when(
                $filters['store_id'] ?? null,
                fn($query, $storeId) => $query->forStore(
                    (int) $storeId
                )
            )

            ->when(
                $filters['driver_id'] ?? null,
                fn($query, $driverId) => $query->forDriver(
                    (int) $driverId
                )
            )

            ->when(
                $filters['customer_id'] ?? null,
                fn($query, $customerId) => $query->forCustomer(
                    (int) $customerId
                )
            )

            ->when(
                $filters['without_driver'] ?? false,
                fn($query) => $query->withoutDriver()
            )

            ->when(
                $filters['today'] ?? false,
                fn($query) => $query->today()
            )

            ->when(
                $filters['older_than_minutes'] ?? null,
                fn($query, $minutes) => $query->olderThanMinutes(
                    (int) $minutes
                )
            )

            ->latest('id')
            ->paginate($perPage);
    }

    public function loadDetails(Order $order): Order
    {
        $order->load([
            'customer:id,name',
            'store:id,name',
            'driver:id,user_id',
            'driver.user:id,name',
            'items:id,order_id,product_id,quantity,price',
            'items.product:id,name',
        ]);

        return $order;
    }
}
