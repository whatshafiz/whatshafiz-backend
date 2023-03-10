<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ComplaintPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('complaints.list');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param Complaint $complaint
     * @return bool
     */
    public function view(User $user, Complaint $complaint): bool
    {
        return $complaint->isOwnedByUser($user) ||
            $user->hasPermissionTo('complaints.list');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param Complaint $complaint
     * @return bool
     */
    public function update(User $user, Complaint $complaint): bool
    {
        return $complaint->isOwnedByUser($user) ||
            $user->hasPermissionTo('complaints.update');
    }
}
