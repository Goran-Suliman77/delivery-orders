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

    public function scopeForDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    public function scopeWithoutDriver(Builder $query): Builder
    {
        return $query->whereNull('driver_id');
    }

    public function scopeOlderThanMinutes($query, int $minutes)
    {
        return $query->where(
            'created_at',
            '<',
            now()->subMinutes($minutes)
        );
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
