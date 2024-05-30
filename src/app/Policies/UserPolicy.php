<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @param  Request  $request
     * @return bool
     */
    public function viewAny(User $user, Request $request): bool
    {
        return $user->hasPermissionTo('users.list') ||
            ($request->course_id && $user->courses()->where('courses.id', $request->course_id)->exists()) ||
            ($request->whatsapp_group_id && $user->whatsappGroups()->where('whatsapp_groups.id', $request->whatsapp_group_id)->exists());
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  User  $user
     * @param  User  $relatedUser
     * @return bool
     */
    public function view(User $user, User $relatedUser): bool
    {
        return $user->hasPermissionTo('users.list') ||
            $user->hasPermissionTo('users.view') ||
            in_array($relatedUser->id, $user->courses()->with('users')->get()->pluck('users.*.id')->first()) ||
            in_array($relatedUser->id, $user->whatsappGroups()->with('users')->get()->pluck('users.*.user_id')->first());
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  User  $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('users.update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('users.delete');
    }
}
