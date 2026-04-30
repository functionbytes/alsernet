<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Helpcenters\HelpcenterArticle;

class HelpcenterArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HelpcenterArticle $article): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, HelpcenterArticle $article): bool
    {
        return $user->id === $article->author_id || $user->hasRole('super-admin');
    }

    public function delete(User $user, HelpcenterArticle $article): bool
    {
        return $user->id === $article->author_id || $user->hasRole('super-admin');
    }
}
