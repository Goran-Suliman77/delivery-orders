<?php

namespace App\Contracts;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StoreQueryServiceInterface
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator;

    public function findPublicById(
        int $storeId
    ): ?Store;
}
