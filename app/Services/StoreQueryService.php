<?php

namespace App\Services;

use App\Contracts\StoreQueryServiceInterface;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class StoreQueryService implements StoreQueryServiceInterface
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 20), 1),
            100
        );

        return $this->publicQuery()
            ->when(
                $filters['type'] ?? null,
                fn($query, $type) => $query->ofType($type)
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findPublicById(
        int $storeId
    ): ?Store {
        return $this->publicQuery()->find($storeId);
    }

    private function publicQuery(): Builder
    {
        return Store::query()
            ->select([
                'id',
                'name',
                'type',
            ])
            ->withCount([
                'products as products_count' =>
                fn(Builder $query) => $query->active(),
            ]);
    }
}
