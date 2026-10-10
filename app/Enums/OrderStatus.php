<?php

namespace App\Enums;

enum OrderStatus: string
{
    //
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [
                self::Confirmed,
                self::Cancelled,
            ],

            self::Confirmed => [
                self::Preparing,
                self::Cancelled,
            ],

            self::Preparing => [
                self::Ready,
                self::Cancelled,
            ],

            self::Ready => [
                self::Assigned,
                self::Cancelled,
            ],

            self::Assigned => [
                self::PickedUp,
                self::Cancelled,
            ],

            self::PickedUp => [
                self::Delivered,
            ],

            self::Delivered,
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array(
            $next,
            $this->allowedTransitions(),
            true
        );
    }
}
