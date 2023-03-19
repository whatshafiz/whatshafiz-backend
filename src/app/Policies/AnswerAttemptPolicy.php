<?php

namespace App\Policies;

use App\Models\AnswerAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class AnswerAttemptPolicy
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
        return $user->hasPermissionTo('answerAttempts.list') || $user->id === $request->user_id;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param AnswerAttempt $answerAttempt
     * @return bool
     */
    public function view(User $user, AnswerAttempt $answerAttempt): bool
    {
        return $user->hasPermissionTo('answerAttempts.view') || $user->id === $answerAttempt->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('answerAttempts.delete');
    }
}
