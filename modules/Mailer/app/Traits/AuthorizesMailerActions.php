<?php

namespace Modules\Mailer\Traits;

use Illuminate\Auth\Access\AuthorizationException;

/**
 * Trait for authorizing Mailer module actions
 * Provides methods to check if user has permission for specific Mailer operations
 */
trait AuthorizesMailerActions
{
    /**
     * Authorize a Mailer action or throw an exception
     *
     * @param string $action The permission name (e.g., 'mailer.templates.view')
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

    /**
     * Check if user can view Mailer templates
     */
    protected function canViewTemplates(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.view') ?? false;
    }

    /**
     * Check if user can create Mailer templates
     */
    protected function canCreateTemplates(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.create') ?? false;
    }

    /**
     * Check if user can update Mailer templates
     */
    protected function canUpdateTemplates(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.update') ?? false;
    }

    /**
     * Check if user can delete Mailer templates
     */
    protected function canDeleteTemplates(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.templates.delete') ?? false;
    }

    /**
     * Check if user can view Mailer components
     */
    protected function canViewComponents(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.components.view') ?? false;
    }

    /**
     * Check if user can create Mailer components
     */
    protected function canCreateComponents(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.components.create') ?? false;
    }

    /**
     * Check if user can update Mailer components
     */
    protected function canUpdateComponents(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.components.update') ?? false;
    }

    /**
     * Check if user can delete Mailer components
     */
    protected function canDeleteComponents(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.components.delete') ?? false;
    }

    /**
     * Check if user can view Mailer variables
     */
    protected function canViewVariables(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.variables.view') ?? false;
    }

    /**
     * Check if user can create Mailer variables
     */
    protected function canCreateVariables(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.variables.create') ?? false;
    }

    /**
     * Check if user can update Mailer variables
     */
    protected function canUpdateVariables(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.variables.update') ?? false;
    }

    /**
     * Check if user can delete Mailer variables
     */
    protected function canDeleteVariables(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.variables.delete') ?? false;
    }

    /**
     * Check if user can view Mailer endpoints
     */
    protected function canViewEndpoints(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.endpoints.view') ?? false;
    }

    /**
     * Check if user can create Mailer endpoints
     */
    protected function canCreateEndpoints(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.endpoints.create') ?? false;
    }

    /**
     * Check if user can update Mailer endpoints
     */
    protected function canUpdateEndpoints(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.endpoints.update') ?? false;
    }

    /**
     * Check if user can delete Mailer endpoints
     */
    protected function canDeleteEndpoints(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.endpoints.delete') ?? false;
    }

    /**
     * Check if user can regenerate Mailer endpoint tokens
     */
    protected function canRegenerateEndpointToken(): bool
    {
        return auth()->user()?->hasPermissionTo('mailer.endpoints.regenerate-token') ?? false;
    }
}
