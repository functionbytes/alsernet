<?php

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\BlogPostComment;

class BlogCommentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.comment.moderate');
    }

    public function update(User $user, BlogPostComment $comment): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.comment.moderate');
    }

    public function delete(User $user, BlogPostComment $comment): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.comment.moderate');
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }
}
