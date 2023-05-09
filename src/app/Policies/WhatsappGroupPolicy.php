<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class WhatsappGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @param  null|int  $userId
     * @return bool
     */
    public function viewAny(User $user, ?int $userId = null): bool
    {
        return $user->hasPermissionTo('whatsappGroups.list') || $user->id === $userId;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  User  $user
     * @param  WhatsappGroup  $whatsappGroup
     * @return bool
     */
    public function view(User $user, WhatsappGroup $whatsappGroup): bool
    {
        return $user->hasPermissionTo('whatsappGroups.list') ||
            $user->hasPermissionTo('whatsappGroups.view') ||
            $whatsappGroup->hasUser($user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('whatsappGroups.create');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @param  WhatsappGroup  $whatsappGroup
     * @return bool
     */
    public function update(User $user, WhatsappGroup $whatsappGroup): bool
    {
        return $user->hasPermissionTo('whatsappGroups.update') ||
            $whatsappGroup->moderator()->where('user_id', $user->id)->count() > 0;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('whatsappGroups.delete');
    }
}
