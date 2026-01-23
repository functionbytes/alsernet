<?php

namespace Modules\Webhook\Policies;

use App\Models\User;
use Modules\Webhook\Models\WebhookIntegration;

class WebhookPolicy
{
    /**
     * Determine if the user can view any webhooks.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.view');
    }

    /**
     * Determine if the user can view the webhook.
     */
    public function view(User $user, WebhookIntegration $integration): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create webhooks.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.create');
    }

    /**
     * Determine if the user can update the webhook.
     */
    public function update(User $user, WebhookIntegration $integration): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.update');
    }

    /**
     * Determine if the user can delete the webhook.
     */
    public function delete(User $user, WebhookIntegration $integration): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.delete');
    }

    /**
     * Determine if the user can monitor webhook deliveries.
     */
    public function monitor(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.monitor');
    }
}
