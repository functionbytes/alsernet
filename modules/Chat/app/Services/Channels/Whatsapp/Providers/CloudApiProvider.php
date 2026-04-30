<?php

namespace Modules\Chat\Services\Channels\Whatsapp\Providers;

use Illuminate\Http\Request;
use Modules\Chat\Jobs\Webhooks\ProcessWhatsappMessageJob;
use Modules\Chat\Services\Channels\Whatsapp\AbstractWhatsappProvider;

class CloudApiProvider extends AbstractWhatsappProvider
{
    protected CloudApiService $service;

    public function __construct($whatsapp)
    {
        parent::__construct($whatsapp);
        $this->service = app(CloudApiService::class);
    }

    protected function getApiUrl(): string
    {
        $base = rtrim(config('channels.whatsapp.api_url', 'https://graph.facebook.com/'), '/');
        $version = config('channels.whatsapp.api_version', 'v22.0');

        return "{$base}/{$version}";
    }

    protected function getApiCredential(): string
    {
        return $this->whatsapp->getApiCredential();
    }

    public function handleWebhook(Request $request, array $data): void
    {
        // Process messages
        if (isset($data['messages'])) {
            foreach ($data['messages'] as $message) {
                ProcessWhatsappMessageJob::dispatch(
                    $this->whatsapp,
                    $message,
                    'cloud_api'
                );
            }
        }

        // Process statuses
        if (isset($data['statuses'])) {
            foreach ($data['statuses'] as $status) {
                $this->processStatusUpdate($status);
            }
        }
    }

    /**
     * Process message status update.
     */
    protected function processStatusUpdate(array $status): void
    {
        \Log::info('Cloud API status update', [
            'whatsapp_id' => $this->whatsapp->id,
            'status' => $status,
        ]);

        // Can be implemented to update message delivery status in database
    }

    public function sendTextMessage(string $to, string $message): array
    {
        return $this->service->sendTextMessage(
            $this->getApiCredential(),
            $this->whatsapp->getPhoneNumberId(),
            $to,
            $message
        );
    }

    public function sendMediaMessage(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): array
    {
        return $this->service->sendMediaMessage(
            $this->getApiCredential(),
            $this->whatsapp->getPhoneNumberId(),
            $to,
            $mediaUrl,
            $mediaType,
            $caption
        );
    }

    public function sendLocationMessage(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        throw new \Exception('Location messages not implemented for Cloud API');
    }

    public function getConnectionStatus(): array
    {
        return ['status' => 'connected'];
    }

    public function connect(): array
    {
        throw new \Exception('Connect not supported for Cloud API');
    }

    public function disconnect(): bool
    {
        throw new \Exception('Disconnect not supported for Cloud API');
    }

    public function restart(): array
    {
        throw new \Exception('Restart not supported for Cloud API');
    }

    public function getSettings(): array
    {
        // WhatsApp Cloud API settings are managed in Facebook Business Manager
        // Return current provider configuration stored in database
        return [
            'provider' => 'whatsapp_cloud',
            'config' => $this->whatsapp->provider_config ?? [],
            'note' => 'Settings are managed in Facebook Business Manager',
            'business_account_id' => $this->whatsapp->provider_config['business_account_id'] ?? null,
            'phone_number_id' => $this->whatsapp->provider_config['phone_number_id'] ?? null,
        ];
    }

    public function updateSettings(array $settings): array
    {
        // Update database configuration only
        // Actual WhatsApp settings must be changed in Facebook Business Manager
        $config = $this->whatsapp->provider_config ?? [];

        // Merge new settings with existing config
        $config = array_merge($config, $settings);

        $this->whatsapp->update([
            'provider_config' => $config,
        ]);

        return [
            'success' => true,
            'message' => 'Local configuration updated. WhatsApp settings must be changed in Facebook Business Manager.',
            'config' => $config,
        ];
    }

    public function fetchContacts(): array
    {
        // WhatsApp Business API doesn't allow fetching contacts proactively
        // This is a privacy restriction by WhatsApp
        \Log::info('fetchContacts called but not supported by WhatsApp Cloud API', [
            'whatsapp_id' => $this->whatsapp->id,
        ]);

        return [
            'success' => false,
            'message' => 'Fetching contacts is not supported by WhatsApp Cloud API due to privacy policies',
            'contacts' => [],
        ];
    }

    public function fetchChats(): array
    {
        // WhatsApp Business API doesn't allow fetching chats proactively
        // Conversations are created when customers message first
        \Log::info('fetchChats called but not supported by WhatsApp Cloud API', [
            'whatsapp_id' => $this->whatsapp->id,
        ]);

        return [
            'success' => false,
            'message' => 'Fetching chats is not supported by WhatsApp Cloud API. Conversations are created when customers initiate contact.',
            'chats' => [],
        ];
    }

    public function deleteInstance(): bool
    {
        // WhatsApp Cloud API doesn't have "instances" like Evolution API
        // Just mark as inactive in database
        $this->whatsapp->update(['active' => false]);

        return true;
    }

    public function isWithinMessageWindow(): bool
    {
        return true; // Cloud API has 24h window
    }

    public function getMessageTemplates(): array
    {
        $config = $this->whatsapp->provider_config;

        return $this->service->getTemplates(
            $this->getApiCredential(),
            $config['business_account_id']
        );
    }

    public function setWebhook(string $webhookUrl, array $events = []): array
    {
        // Webhooks for WhatsApp Cloud API are configured in Facebook App Dashboard
        // We can't set them programmatically without app-level access token
        \Log::info('setWebhook called for WhatsApp Cloud API', [
            'whatsapp_id' => $this->whatsapp->id,
            'webhook_url' => $webhookUrl,
        ]);

        return [
            'success' => false,
            'message' => 'Webhooks for WhatsApp Cloud API must be configured in Facebook App Dashboard',
            'instructions' => [
                '1. Go to Facebook App Dashboard',
                '2. Navigate to WhatsApp > Configuration',
                '3. Set Callback URL to: '.$webhookUrl,
                '4. Set Verify Token from your configuration',
                '5. Subscribe to webhook fields: messages, message_status',
            ],
            'webhook_url' => $webhookUrl,
        ];
    }
}
