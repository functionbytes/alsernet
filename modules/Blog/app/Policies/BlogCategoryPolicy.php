<?php

namespace Modules\Blog\Policies;

use App\Models\User;
use Modules\Blog\Models\BlogCategory;

class BlogCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.category.view');
    }

    public function view(User $user, BlogCategory $category): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.category.view');
    }

    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.category.create');
    }

    public function update(User $user, BlogCategory $category): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.category.update');
    }

    public function delete(User $user, BlogCategory $category): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('blog.category.delete');
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }
}
