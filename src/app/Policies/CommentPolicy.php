<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
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
        return $user->hasPermissionTo('comments.list') || $user->id === $userId;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @param Comment $comment
     * @return bool
     */
    public function view(User $user, Comment $comment): bool
    {
        return $comment->isCommentedBy($user) || $user->hasPermissionTo('comments.list');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param Comment $comment
     * @return bool
     */
    public function update(User $user, Comment $comment): bool
    {
        return (!$comment->is_approved && $comment->isCommentedBy($user)) ||
            $user->hasPermissionTo('comments.update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Comment $comment
     * @return bool
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $comment->isCommentedBy($user) || $user->hasPermissionTo('comments.delete');
    }
}
