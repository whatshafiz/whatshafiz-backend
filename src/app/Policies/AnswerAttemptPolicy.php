<?php

namespace App\Policies;

use App\Models\AnswerAttempt;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnswerAttemptPolicy
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
        return $user->hasPermissionTo('answerattempts.list');
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
        return $user->hasPermissionTo('answerattempts.view')
            || $user->id === $answerAttempt->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param AnswerAttempt $answerAttempt
     * @return bool
     */
    public function update(User $user, AnswerAttempt $answerAttempt): bool
    {
        return $user->hasPermissionTo('answerattempts.update') ||
            $user->id === $answerAttempt->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('answerattempts.delete');
    }
}
