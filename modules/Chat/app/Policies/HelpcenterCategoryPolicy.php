<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Helpcenters\HelpcenterCategory;

class HelpcenterCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HelpcenterCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, HelpcenterCategory $category): bool
    {
        return true;
    }

    public function delete(User $user, HelpcenterCategory $category): bool
    {
        return true;
    }
}
