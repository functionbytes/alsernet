<?php

namespace Modules\Mailer\Policies;

use App\Models\User;
use Modules\Mailer\Models\MailerEndpoint;

class MailerEndpointPolicy
{
    /**
     * Determine if the user can view any mailer endpoints
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.view');
    }

    /**
     * Determine if the user can view a mailer endpoint
     */
    public function view(User $user, MailerEndpoint $endpoint): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.view');
    }

    /**
     * Determine if the user can create mailer endpoints
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.create');
    }

    /**
     * Determine if the user can update a mailer endpoint
     */
    public function update(User $user, MailerEndpoint $endpoint): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.update');
    }

    /**
     * Determine if the user can delete a mailer endpoint
     */
    public function delete(User $user, MailerEndpoint $endpoint): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.delete');
    }

    /**
     * Determine if the user can view endpoint logs
     */
    public function viewLogs(User $user, MailerEndpoint $endpoint): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.logs');
    }

    /**
     * Determine if the user can regenerate endpoint token
     */
    public function regenerateToken(User $user, MailerEndpoint $endpoint): bool
    {
        return $user->hasPermissionTo('mailer.endpoints.regenerate-token');
    }
}
