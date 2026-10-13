<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductQueryServiceInterface
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator;

    public function findPublicById(
        int $productId
    ): ?Product;

    public function paginateForStore(
        Store $store,
        int $perPage
    ): LengthAwarePaginator;
}
