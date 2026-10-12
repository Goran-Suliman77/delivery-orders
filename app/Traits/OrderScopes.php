<?php

namespace App\Traits;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Carbon\CarbonInterface;

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

    public function scopeStatus(Builder $query, OrderStatus|string $status): Builder
    {
        $value = $status instanceof OrderStatus
            ? $status->value
            : $status;

        return $query->where('status', $value);
    }

    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }
    public function scopeWithoutDriver(Builder $query): Builder
    {
        return $query->whereNull('driver_id');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
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
        $start = today();
        $end = $start->copy()->addDay();

        return $query->createdBetween($start, $end);
    }

    public function scopeForStores(
        Builder $query,
        array $storeIds
    ): Builder {
        return $query->whereIn('store_id', $storeIds);
    }

    public function scopeForDrivers(
        Builder $query,
        array $driverIds
    ): Builder {
        return $query->whereIn('driver_id', $driverIds);
    }

    public function scopeCreatedBetween(
        Builder $query,
        CarbonInterface $start,
        CarbonInterface $endExclusive
    ): Builder {
        return $query
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive);
    }
}
