<?php

namespace App\Services;

use App\Models\Store;

class DeliveryFeeService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function calculate(Store $store): string
    {
        return (string) config(
            'delivery.delivery.base_fee',
            '4.00'
        );
    }
}
