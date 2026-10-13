<?php

namespace App\Contracts;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StoreManagementServiceInterface
{
    public function paginateForAdministration(
        array $filters
    ): LengthAwarePaginator;

    public function create(array $data): Store;

    public function findForAdministration(
        int $storeId
    ): ?Store;

    public function update(
        Store $store,
        array $data
    ): Store;

    public function setActive(
        Store $store,
        bool $active
    ): array;
}
