<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('stores.manage');
    }
}
