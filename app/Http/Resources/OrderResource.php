<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'status' => $this->status->value,

            'created_at' => $this->created_at?->toISOString(),

            'customer' => $this->whenLoaded(
                'customer',
                fn() => [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                ]
            ),

            'store' => $this->whenLoaded(
                'store',
                fn() => [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ]
            ),

            'driver' => $this->whenLoaded(
                'driver',
                fn() => [
                    'id' => $this->driver->id,
                    'name' => $this->driver->user?->name,
                ]
            ),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            'pricing' => [
                'subtotal' => $this->subtotal,
                'delivery_fee' => $this->delivery_fee,
                'total' => $this->total,
            ],
        ];
    }
}
