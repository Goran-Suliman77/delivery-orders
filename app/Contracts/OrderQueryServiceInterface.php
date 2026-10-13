<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderQueryServiceInterface
{
    public function paginateVisibleTo(
        User $user,
        array $filters
    ): LengthAwarePaginator;

    public function loadDetails(Order $order): Order;
}
