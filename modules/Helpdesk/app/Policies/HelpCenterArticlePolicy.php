<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\HelpCenterArticle;

class HelpCenterArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Published articles are public in widget
    }

    public function view(User $user, HelpCenterArticle $article): bool
    {
        return ! $article->draft || $user->hasPermissionTo('manage_helpdesk');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function update(User $user, HelpCenterArticle $article): bool
    {
        return $user->hasPermissionTo('manage_helpdesk')
            || $article->author_id === $user->id;
    }

    public function delete(User $user, HelpCenterArticle $article): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }
}
