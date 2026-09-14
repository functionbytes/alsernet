<?php

namespace Modules\HelpdeskSocial\Contracts;

use Modules\HelpdeskSocial\Models\SocialAccount;

/**
 * Contract for any social channel provider (Meta, TikTok, X, LinkedIn, etc.)
 */
interface SocialChannelInterface
{
    /**
     * Get the unique identifier for this channel.
     */
    public function getIdentifier(): string;

    /**
     * Get the display name for this channel.
     */
    public function getDisplayName(): string;

    /**
     * Check if this channel supports comments on posts.
     */
    public function supportsComments(): bool;

    /**
     * Check if this channel supports direct messages.
     */
    public function supportsMessages(): bool;

    /**
     * Check if this channel supports webhooks for real-time events.
     */
    public function supportsWebhooks(): bool;

    /**
     * Get the required permissions/scopes for API access.
     *
     * @return array<int, string>
     */
    public function getRequiredPermissions(): array;

    /**
     * Validate the account configuration before saving.
     *
     * @throws \InvalidArgumentException
     */
    public function validateAccount(SocialAccount $account): void;

    /**
     * Get the webhook verification handler for this channel.
     */
    public function getWebhookVerifier(): WebhookVerifierInterface;

    /**
     * Get the API client for this channel.
     */
    public function getApiClient(): SocialApiClientInterface;
}
