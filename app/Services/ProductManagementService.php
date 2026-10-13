<?php

namespace App\Services;

use App\Contracts\ProductManagementServiceInterface;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductManagementService implements ProductManagementServiceInterface
{
    public function paginateForStore(
        Store $store,
        int $perPage
    ): LengthAwarePaginator {
        $perPage = min(max($perPage, 1), 100);

        return Product::query()
            ->forStore((int) $store->id)
            ->select([
                'id',
                'store_id',
                'name',
                'price',
                'is_active',
            ])
            ->latest('id')
            ->paginate($perPage);
    }

    public function createForStore(
        Store $store,
        array $data
    ): Product {
        return $store->products()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'is_active' => true,
        ]);
    }

    public function findForAdministration(
        int $productId
    ): ?Product {
        return Product::query()
            ->select([
                'id',
                'store_id',
                'name',
                'price',
                'is_active',
            ])
            ->find($productId);
    }

    public function update(
        Product $product,
        array $data
    ): Product {
        $product->fill($data);
        $product->save();

        return $product;
    }

    public function setActive(
        Product $product,
        bool $active
    ): array {
        $changed = (bool) $product->is_active !== $active;

        if ($changed) {
            $product->is_active = $active;
            $product->save();
        }

        return [
            'product' => $product,
            'changed' => $changed,
        ];
    }
}
