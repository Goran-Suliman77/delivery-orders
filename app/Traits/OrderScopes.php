<?php

namespace App\Traits;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;

//local scopes for order model
trait OrderScopes
{
    //
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            OrderStatus::Delivered->value,
            OrderStatus::Cancelled->value,
        ]);
    }

    public function scopeStatus( Builder $query, OrderStatus|string $status ): Builder
    {
        $value = $status instanceof OrderStatus
            ? $status->value
            : $status;

        return $query->where('status', $value);
    }

    public function scopeForDriver(  Builder $query, int $driverId ): Builder
    {
        return $query->where('driver_id', $driverId);
    }
    public function scopeWithoutDriver(Builder $query): Builder
    {
        return $query->whereNull('driver_id');
    }

    public function scopeForStore( Builder $query,int $storeId): Builder
     {
        return $query->where('store_id', $storeId);
    }

    public function scopeForCustomer(
    Builder $query,
    int $customerId
): Builder {
    return $query->where('customer_id', $customerId);
}

    public function scopeOlderThanMinutes(Builder $query, int $minutes): Builder
    {
        return $query->where(
            'created_at',
            '<',
            now()->subMinutes($minutes)
        );
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }
}
