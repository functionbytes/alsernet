<?php

namespace Modules\Social\Services\OAuth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class BaseOAuthService
{
    /**
     * Redirect to OAuth provider
     */
    abstract public function redirectToProvider(): RedirectResponse;

    /**
     * Handle OAuth callback and return account data
     */
    abstract public function handleCallback(Request $request): array;

    /**
     * Refresh access token
     */
    abstract public function refreshToken(string $refreshToken): array;

    /**
     * Get user profile from provider
     */
    abstract public function getUserProfile(string $accessToken): array;

    /**
     * Validate webhook signature
     */
    public function validateWebhook(Request $request): bool
    {
        return true;
    }
}
