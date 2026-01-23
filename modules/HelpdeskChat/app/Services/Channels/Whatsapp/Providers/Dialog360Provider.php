<?php

namespace Modules\HelpdeskChat\Services\Channels\Whatsapp\Providers;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Services\Channels\Whatsapp\AbstractWhatsappProvider;

class Dialog360Provider extends AbstractWhatsappProvider
{
    protected Dialog360Service $service;

    public function __construct($whatsapp)
    {
        parent::__construct($whatsapp);
        $this->service = app(Dialog360Service::class);
    }

    protected function getApiUrl(): string
    {
        return 'https://waba.360dialog.io/v1';
    }

    protected function getApiCredential(): string
    {
        return $this->whatsapp->getApiCredential();
    }

    public function handleWebhook(Request $request, array $data): void
    {
        // Dialog360 uses same webhook format as Cloud API
        // Process messages
        if (isset($data['messages'])) {
            foreach ($data['messages'] as $message) {
                \Modules\HelpdeskChat\Jobs\Webhooks\ProcessWhatsappMessageJob::dispatch(
                    $this->whatsapp,
                    $message,
                    'dialog360'
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
        \Log::info('Dialog360 status update', [
            'whatsapp_id' => $this->whatsapp->id,
            'status' => $status,
        ]);

        // Can be implemented to update message delivery status in database
    }

    public function sendTextMessage(string $to, string $message): array
    {
        return $this->service->sendTextMessage($this->getApiCredential(), $to, $message);
    }

    public function sendMediaMessage(string $to, string $mediaUrl, string $mediaType, ?string $caption = null): array
    {
        return $this->service->sendMediaMessage($this->getApiCredential(), $to, $mediaUrl, $mediaType, $caption);
    }

    public function sendLocationMessage(string $to, float $latitude, float $longitude, ?string $name = null, ?string $address = null): array
    {
        throw new \Exception('Location messages not implemented for Dialog360');
    }

    public function getConnectionStatus(): array
    {
        return ['status' => 'connected'];
    }

    public function connect(): array
    {
        throw new \Exception('Connect not supported for Dialog360');
    }

    public function disconnect(): bool
    {
        throw new \Exception('Disconnect not supported for Dialog360');
    }

    public function restart(): array
    {
        throw new \Exception('Restart not supported for Dialog360');
    }

    public function getSettings(): array
    {
        // 360Dialog settings are managed through their portal
        // Return current provider configuration stored in database
        return [
            'provider' => 'dialog360',
            'config' => $this->whatsapp->provider_config ?? [],
            'note' => 'Settings are managed in 360Dialog Partner Portal',
            'api_key' => $this->whatsapp->provider_config['api_key'] ? '***'.substr($this->whatsapp->provider_config['api_key'], -4) : null,
        ];
    }

    public function updateSettings(array $settings): array
    {
        // Update database configuration only
        // Actual 360Dialog settings must be changed in their portal
        $config = $this->whatsapp->provider_config ?? [];

        // Merge new settings with existing config
        $config = array_merge($config, $settings);

        $this->whatsapp->update([
            'provider_config' => $config,
        ]);

        return [
            'success' => true,
            'message' => 'Local configuration updated. 360Dialog settings must be changed in their Partner Portal.',
            'config' => $config,
        ];
    }

    public function fetchContacts(): array
    {
        // WhatsApp Business API (via 360Dialog) doesn't allow fetching contacts proactively
        // This is a privacy restriction by WhatsApp
        \Log::info('fetchContacts called but not supported by 360Dialog', [
            'whatsapp_id' => $this->whatsapp->id,
        ]);

        return [
            'success' => false,
            'message' => 'Fetching contacts is not supported by 360Dialog due to WhatsApp privacy policies',
            'contacts' => [],
        ];
    }

    public function fetchChats(): array
    {
        // WhatsApp Business API doesn't allow fetching chats proactively
        // Conversations are created when customers message first
        \Log::info('fetchChats called but not supported by 360Dialog', [
            'whatsapp_id' => $this->whatsapp->id,
        ]);

        return [
            'success' => false,
            'message' => 'Fetching chats is not supported by 360Dialog. Conversations are created when customers initiate contact.',
            'chats' => [],
        ];
    }

    public function deleteInstance(): bool
    {
        // 360Dialog doesn't have "instances" like Evolution API
        // Just mark as inactive in database
        $this->whatsapp->update(['active' => false]);

        return true;
    }

    public function isWithinMessageWindow(): bool
    {
        return true;
    }

    public function getMessageTemplates(): array
    {
        return $this->service->getTemplates($this->getApiCredential());
    }

    public function setWebhook(string $webhookUrl, array $events = []): array
    {
        // Webhooks for 360Dialog are configured in their Partner Portal
        \Log::info('setWebhook called for 360Dialog', [
            'whatsapp_id' => $this->whatsapp->id,
            'webhook_url' => $webhookUrl,
        ]);

        return [
            'success' => false,
            'message' => 'Webhooks for 360Dialog must be configured in 360Dialog Partner Portal',
            'instructions' => [
                '1. Log in to 360Dialog Partner Portal',
                '2. Select your WhatsApp Business Account',
                '3. Go to Webhooks section',
                '4. Set Callback URL to: '.$webhookUrl,
                '5. Subscribe to events: messages, statuses',
            ],
            'webhook_url' => $webhookUrl,
        ];
    }
}
