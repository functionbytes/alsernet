<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Webhook;

class WebhookPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.settings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.settings.update');
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->can('remarketing.settings.update') && $this->owns($user, $webhook);
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->can('remarketing.settings.update') && $this->owns($user, $webhook);
    }

    protected function owns(User $user, Webhook $webhook): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $webhook->store?->user_id === $user->id;
    }
}
