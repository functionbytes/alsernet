<?php

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\BlogPost;

class BlogPostPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasAnyPermission(['blog.post.view', 'blog.post.view-all']);
    }

    public function view(User $user, BlogPost $post): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($user->hasPermissionTo('blog.post.view-all')) {
            return true;
        }

        return $user->hasPermissionTo('blog.post.view') && $post->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.post.create');
    }

    public function update(User $user, BlogPost $post): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($user->hasPermissionTo('blog.post.view-all') && $user->hasPermissionTo('blog.post.update')) {
            return true;
        }

        return $user->hasPermissionTo('blog.post.update') && $post->user_id === $user->id;
    }

    public function delete(User $user, BlogPost $post): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.post.delete');
    }

    public function publish(User $user, BlogPost $post): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.post.publish');
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }
}
