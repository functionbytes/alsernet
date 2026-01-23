<?php

namespace Modules\Webhook\Policies;

use App\Models\User;

class SettingsPolicy
{
    /**
     * Determine if the user can configure webhooks.
     */
    public function configure(User $user): bool
    {
        // Super-admin siempre puede
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Managers pueden configurar webhooks
        if ($user->hasRole('manager')) {
            return $user->can('manager.webhooks.configure');
        }

        return false;
    }

    /**
     * Determine if the user can view webhook backups.
     */
    public function viewSettings(User $user): bool
    {
        return $this->configure($user);
    }

    /**
     * Determine if the user can manage webhook integrations.
     */
    public function manageIntegrations(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.manage-integrations');
    }

    /**
     * Determine if the user can manage webhook subscriptions.
     */
    public function manageSubscriptions(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.manage-subscriptions');
    }

    /**
     * Determine if the user can manage webhook events.
     */
    public function manageEvents(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.manage-events');
    }

    /**
     * Determine if the user can test webhooks.
     */
    public function testWebhooks(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('manager.webhooks.test');
    }
}
