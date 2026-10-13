<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductManagementServiceInterface
{
    public function paginateForStore(
        Store $store,
        int $perPage
    ): LengthAwarePaginator;

    public function createForStore(
        Store $store,
        array $data
    ): Product;

    public function findForAdministration(
        int $productId
    ): ?Product;

    public function update(
        Product $product,
        array $data
    ): Product;

    /**
     * @return array{product: Product, changed: bool}
     */
    public function setActive(
        Product $product,
        bool $active
    ): array;
}
