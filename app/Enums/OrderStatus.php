<?php

namespace App\Enums;

enum OrderStatus:string
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

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::Ready => 'Ready',
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked Up',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }
}
