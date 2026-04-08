<?php

namespace Modules\Mailer\Traits;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait for authorizing Mailer module actions
 */
trait AuthorizesMailerActions
{
    /**
     * Authorize a Mailer action or throw an exception
     *
     * @throws AuthorizationException
     */
    protected function authorizeMailerAction(string $action): void
    {
        if (! auth()->user() || ! auth()->user()->hasPermissionTo($action)) {
            throw new AuthorizationException(
                "You do not have permission to perform this action: {$action}"
            );
        }
    }
}
