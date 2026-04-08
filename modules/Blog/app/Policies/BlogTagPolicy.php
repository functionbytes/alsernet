<?php

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\BlogTag;

class BlogTagPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.tag.view');
    }

    public function view(User $user, BlogTag $tag): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.tag.view');
    }

    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.tag.create');
    }

    public function update(User $user, BlogTag $tag): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.tag.update');
    }

    public function delete(User $user, BlogTag $tag): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.tag.delete');
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }
}
