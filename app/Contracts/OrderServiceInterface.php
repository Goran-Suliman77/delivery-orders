<?php

namespace App\Contracts;

use App\Models\Order;

interface OrderServiceInterface
{
    public function create(
        int $customerId,
        int $storeId,
        array $items
    ): Order;
}
