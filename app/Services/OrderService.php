<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private OrderPricingService $pricingService
    ) {}

    public function create(
        int $customerId,
        int $storeId,
        array $items
    ): Order {
        return DB::transaction(function () use (
            $customerId,
            $storeId,
            $items
        ) {
            /*
             * نعيد التحقق داخل Transaction
             * لأن حالة المتجر قد تتغير بعد الـ Validation.
             */
            $store = Store::query()
                ->lockForUpdate()
                ->find($storeId);

            if (! $store) {
                throw ValidationException::withMessages([
                    'store_id' => [
                        'المتجر غير موجود أو غير متاح حاليًا.'
                    ],
                ]);
            }

            $productIds = collect($items)
                ->pluck('product_id')
                ->map(fn($id) => (int) $id)
                ->values();

            $products = Product::query()
                ->select([
                    'id',
                    'store_id',
                    'name',
                    'price',
                    'is_active',
                ])
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get();

            if ($products->count() !== $productIds->count()) {
                $foundIds = $products
                    ->pluck('id')
                    ->map(fn($id) => (int) $id);

                $missingIds = $productIds
                    ->diff($foundIds)
                    ->values()
                    ->all();

                throw ValidationException::withMessages([
                    'items' => [
                        'يوجد منتج غير موجود أو غير متاح في هذا المتجر.'
                    ],

                    'product_ids' => $missingIds,
                ]);
            }

            $pricing = $this->pricingService->calculate(
                store: $store,
                products: $products,
                items: $items
            );

            $order = Order::create([
                'customer_id' => $customerId,
                'store_id' => $store->id,
                'driver_id' => null,
                'status' => \App\Enums\OrderStatus::Pending,
                'subtotal' => $pricing['subtotal'],
                'delivery_fee' => $pricing['delivery_fee'],
                'total' => $pricing['total'],
            ]);

            $order->items()->createMany(
                $pricing['items']
            );

            return $order->load([
                'customer:id,name',
                'store:id,name',
                'driver:id,user_id',
                'driver.user:id,name',
                'items:id,order_id,product_id,quantity,price',
                'items.product:id,name',
            ]);
        }, attempts: 5);
    }
}
