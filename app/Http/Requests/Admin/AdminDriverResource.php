<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),

            'vehicle_type' => $this->vehicle_type,
            'vehicle_number' => $this->vehicle_number,
            'is_available' => (bool) $this->is_available,

            'active_orders_count' => $this->active_orders_count,

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
