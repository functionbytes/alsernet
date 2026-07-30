<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Webhook;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.view');
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.create');
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.update');
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.webhooks.manage');
    }
}
