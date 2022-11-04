<?php

namespace App\Policies;

use App\Models\Regulation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegulationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function list(User $user)
    {
        return $user->hasPermissionTo('regulations.list');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user)
    {
        return $user->hasPermissionTo('regulations.update');
    }
}
