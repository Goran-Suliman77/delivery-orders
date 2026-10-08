<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Collection;

class OrderPricingService
{
    public function __construct(
        private DeliveryFeeService $deliveryFeeService
    ) {}

    public function calculate(
        Store $store,
        Collection $products,
        array $items
    ): array {
        $productsById = $products->keyBy('id');

        $subtotalCents = 0;
        $orderItems = [];

        foreach ($items as $item) {
            $product = $productsById->get($item['product_id']);

            $quantity = (int) $item['quantity'];

            $priceCents = $this->toCents(
                $product->price
            );

            $lineTotalCents = $priceCents * $quantity;

            $subtotalCents += $lineTotalCents;

            $orderItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ];
        }

        $deliveryFee = $this->deliveryFeeService
            ->calculate($store);

        $deliveryFeeCents = $this->toCents(
            $deliveryFee
        );

        $totalCents = $subtotalCents + $deliveryFeeCents;

        return [
            'subtotal' => $this->fromCents(
                $subtotalCents
            ),

            'delivery_fee' => $this->fromCents(
                $deliveryFeeCents
            ),

            'total' => $this->fromCents(
                $totalCents
            ),

            'items' => $orderItems,
        ];
    }

    private function toCents(string|int|float $amount): int
    {
        $amount = trim((string) $amount);

        if (! str_contains($amount, '.')) {
            return ((int) $amount) * 100;
        }

        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        $decimal = str_pad(
            substr($decimal, 0, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) $decimal;
    }

    private function fromCents(int $cents): string
    {
        return number_format(
            $cents / 100,
            2,
            '.',
            ''
        );
    }
}
