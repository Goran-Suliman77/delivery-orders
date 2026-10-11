<?php

namespace App\Policies;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DriverPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('drivers.view_all')
            || $user->hasPermissionTo('drivers.manage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Driver $driver): bool
    {
        return $user->hasPermissionTo('drivers.view_all')
            || $user->hasPermissionTo('drivers.manage');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Driver $driver): bool
    {
        return $user->hasPermissionTo('drivers.manage');
    }
}
