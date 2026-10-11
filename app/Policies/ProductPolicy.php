<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('products.manage');
    }
}
