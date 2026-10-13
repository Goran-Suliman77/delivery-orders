<?php

namespace App\Contracts;

use App\Enums\OrderStatus;
use App\Models\Order;

interface OrderStatusServiceInterface
{
    public function change(
        int $orderId,
        OrderStatus $newStatus
    ): Order;
}
