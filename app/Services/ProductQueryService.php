<?php

namespace App\Services;

use App\Contracts\ProductQueryServiceInterface;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductQueryService implements ProductQueryServiceInterface
{
    public function paginatePublic(
        array $filters
    ): LengthAwarePaginator {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 20), 1),
            100
        );

        return Product::query()
            ->available()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->with('store:id,name')
            ->when(
                isset($filters['store_id']),
                fn($query) => $query->forStore(
                    (int) $filters['store_id']
                )
            )
            ->latest('id')
            ->paginate($perPage);
    }

    public function findPublicById(
        int $productId
    ): ?Product {
        return Product::query()
            ->available()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->with('store:id,name')
            ->find($productId);
    }

    public function paginateForStore(
        Store $store,
        int $perPage
    ): LengthAwarePaginator {
        $perPage = min(max($perPage, 1), 100);

        return Product::query()
            ->available()
            ->forStore((int) $store->id)
            ->select([
                'id',
                'store_id',
                'name',
                'price',
            ])
            ->latest('id')
            ->paginate($perPage);
    }
}
