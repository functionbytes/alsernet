<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Webhook;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->account_id === $webhook->account_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->account_id === $webhook->account_id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->account_id === $webhook->account_id;
    }

    public function restore(User $user, Webhook $webhook): bool
    {
        return $user->account_id === $webhook->account_id;
    }

    public function forceDelete(User $user, Webhook $webhook): bool
    {
        return $user->account_id === $webhook->account_id;
    }
}
