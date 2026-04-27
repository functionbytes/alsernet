<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageWebhook;

class PageWebhookPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.manage-webhooks');
    }

    public function view(User $user, PageWebhook $webhook): bool
    {
        return $user->can('page.manage-webhooks');
    }

    public function create(User $user): bool
    {
        return $user->can('page.manage-webhooks');
    }

    public function update(User $user, PageWebhook $webhook): bool
    {
        return $user->can('page.manage-webhooks');
    }

    public function delete(User $user, PageWebhook $webhook): bool
    {
        return $user->can('page.manage-webhooks');
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
