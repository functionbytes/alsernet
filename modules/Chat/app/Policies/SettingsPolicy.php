<?php

namespace Modules\Chat\Policies;

use App\Models\User;

class SettingsPolicy
{
    /**
     * Determine if user can configure global chat settings
     */
    public function configure(User $user): bool
    {
        return $user->hasRole('super-chats');
    }

    /**
     * Determine if user can view chat settings
     */
    public function viewSettings(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage teams
     */
    public function manageTeams(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage channels
     */
    public function manageChannels(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage templates (canned responses, email templates)
     */
    public function manageTemplates(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats', 'agent']);
    }

    /**
     * Determine if user can manage automations and macros
     */
    public function manageAutomations(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage webhooks
     */
    public function manageWebhooks(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can view reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats', 'agent']);
    }

    /**
     * Determine if user can export reports
     */
    public function exportReports(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can view audit logs
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage business hours
     */
    public function manageBusinessHours(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage notifications
     */
    public function manageNotifications(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }

    /**
     * Determine if user can manage SLA policies
     */
    public function manageSLAPolicies(User $user): bool
    {
        return $user->hasRole(['super-chats', 'chats']);
    }
}
