<?php

namespace App\Contracts;

use App\Models\Order;

interface DriverAssignmentServiceInterface
{
    public function assign(
        int $orderId,
        int $driverId
    ): Order;
}
