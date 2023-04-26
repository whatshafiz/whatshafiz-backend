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
     * @param  null|int  $userId
     * @return bool
     */
    public function viewAny(User $user, ?int $userId): bool
    {
        return $user->hasPermissionTo('complaints.list') || $user->id === $userId;
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
