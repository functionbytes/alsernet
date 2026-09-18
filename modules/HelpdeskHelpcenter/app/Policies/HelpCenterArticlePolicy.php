<?php

namespace Modules\HelpdeskHelpcenter\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskHelpcenter\Models\HelpCenterArticle;

class HelpCenterArticlePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.articles.view');
    }

    public function view(User $user, HelpCenterArticle $article): bool
    {
        if ($article->is_published) {
            return true;
        }

        return $user->can('helpdesk.helpcenter.articles.view')
            || $article->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.articles.create');
    }

    public function update(User $user, HelpCenterArticle $article): bool
    {
        return $user->can('helpdesk.helpcenter.articles.update')
            || $article->author_id === $user->id;
    }

    public function delete(User $user, HelpCenterArticle $article): bool
    {
        return $user->can('helpdesk.helpcenter.articles.delete');
    }

    public function translate(User $user, HelpCenterArticle $article): bool
    {
        return $user->can('helpdesk.helpcenter.articles.translate')
            || $user->can('helpdesk.helpcenter.articles.update');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.articles.manage');
    }
}
