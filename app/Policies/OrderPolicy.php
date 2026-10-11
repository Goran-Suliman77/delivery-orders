<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('orders.view_all')
            || $user->hasPermissionTo('orders.view_own')
            || $user->hasPermissionTo('orders.view_assigned');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo('orders.view_all')) {
            return true;
        }

        if (
            $user->hasPermissionTo('orders.view_own')
            && (int) $order->customer_id === (int) $user->id
        ) {
            return true;
        }

        if ($user->hasPermissionTo('orders.view_assigned')) {
            $driverId = $user->driver()->value('id');

            return $driverId !== null
                && (int) $order->driver_id === (int) $driverId;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('orders.create');
    }


    public function updateStatus(
        User $user,
        Order $order,
        OrderStatus|string $newStatus
    ): bool {
        if (
            $user->hasPermissionTo('orders.update_any_status')
            || $user->hasPermissionTo('orders.update_status')
        ) {
            return true;
        }

        if (
            ! $user->hasPermissionTo('orders.update_assigned_status')
            || ! $user->hasPermissionTo('orders.view_assigned')
        ) {
            return false;
        }

        $driverId = $user->driver()->value('id');

        if (
            $driverId === null
            || (int) $order->driver_id !== (int) $driverId
        ) {
            return false;
        }

        $statusValue = $newStatus instanceof OrderStatus
            ? $newStatus->value
            : $newStatus;

        return in_array(
            $statusValue,
            [
                OrderStatus::PickedUp->value,
                OrderStatus::Delivered->value,
            ],
            true
        );
    }

    public function assignDriver(
        User $user,
        Order $order
    ): bool {
        return $user->hasPermissionTo('orders.assign_driver');
    }

    public function filterByCustomer(User $user): bool
    {
        return $user->hasPermissionTo('orders.filter_by_customer');
    }




    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return false;
    }
}
