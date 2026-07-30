<?php

namespace Modules\HelpdeskSocial\Services\Channels;

use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Contracts\SocialChannelInterface;
use Modules\HelpdeskSocial\Contracts\WebhookParserInterface;
use Modules\HelpdeskSocial\Contracts\WebhookVerifierInterface;
use Modules\HelpdeskSocial\Models\SocialAccount;

class MetaChannelProvider implements SocialChannelInterface
{
    public function __construct(
        private readonly MetaApiClient $apiClient,
        private readonly MetaWebhookParser $webhookParser,
        private readonly MetaWebhookVerifier $webhookVerifier,
    ) {}

    public function getIdentifier(): string
    {
        return 'meta';
    }

    public function getDisplayName(): string
    {
        return 'Meta (Facebook + Instagram)';
    }

    public function supportsComments(): bool
    {
        return true;
    }

    public function supportsMessages(): bool
    {
        return true;
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function getRequiredPermissions(): array
    {
        return [
            'pages_read_engagement',
            'pages_manage_posts',
            'instagram_basic',
            'instagram_manage_messages',
            'instagram_manage_comments',
        ];
    }

    public function validateAccount(SocialAccount $account): void
    {
        if (blank($account->external_id)) {
            throw new \InvalidArgumentException('El ID externo de la página es requerido.');
        }

        if (blank($account->page_access_token)) {
            throw new \InvalidArgumentException('El token de acceso es requerido.');
        }
    }

    public function getWebhookVerifier(): WebhookVerifierInterface
    {
        return $this->webhookVerifier;
    }

    public function getApiClient(): SocialApiClientInterface
    {
        return $this->apiClient;
    }

    public function getWebhookParser(): WebhookParserInterface
    {
        return $this->webhookParser;
    }
}
