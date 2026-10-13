<?php

namespace App\Services;

use App\Contracts\StoreManagementServiceInterface;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreManagementService implements StoreManagementServiceInterface
{
    public function paginateForAdministration(
        array $filters
    ): LengthAwarePaginator {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 50), 1),
            100
        );

        return Store::query()
            ->forAdministration()
            ->select([
                'id',
                'name',
                'type',
                'is_active',
            ])
            ->withCount('products')
            ->when(
                $filters['type'] ?? null,
                fn($query, $type) => $query->ofType($type)
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(array $data): Store
    {
        $store = Store::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'is_active' => true,
        ]);

        return $store->loadCount('products');
    }

    public function findForAdministration(
        int $storeId
    ): ?Store {
        return Store::query()
            ->forAdministration()
            ->select([
                'id',
                'name',
                'type',
                'is_active',
            ])
            ->withCount('products')
            ->find($storeId);
    }

    public function update(Store $store, array $data): Store
    {
        $store->fill($data);
        $store->save();

        return $store->loadCount('products');
    }

    public function setActive(
        Store $store,
        bool $active
    ): array {
        $changed = (bool) $store->is_active !== $active;

        if ($changed) {
            $store->is_active = $active;
            $store->save();
        }

        $store->refresh();
        $store->loadCount('products');

        return [
            'store' => $store,
            'changed' => $changed,
        ];
    }
}
