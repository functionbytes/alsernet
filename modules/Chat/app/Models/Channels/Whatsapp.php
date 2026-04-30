<?php

namespace Modules\Chat\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Inbox\Inbox;

class Whatsapp extends Model
{
    protected $table = 'chat_channel_whatsapps';

    protected $fillable = [
        'account_id',
        'phone_number',
        'provider',
        'provider_config',
        'webhook_url',
        'message_templates',
        'additional_attributes',
    ];

    protected $casts = [
        'provider_config' => 'array',
        'message_templates' => 'array',
        'additional_attributes' => 'array',
    ];

    /**
     * Get the account that owns the WhatsApp channel.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inbox for this channel (polymorphic).
     */
    public function inbox(): MorphOne
    {
        return $this->morphOne(Inbox::class, 'channel');
    }

    /**
     * Encrypt/decrypt provider config.
     */
    protected function providerConfig(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (! $value) {
                    return [];
                }

                $config = json_decode($value, true);

                // Decrypt sensitive fields
                if (isset($config['api_key'])) {
                    $config['api_key'] = Crypt::decryptString($config['api_key']);
                }
                if (isset($config['access_token'])) {
                    $config['access_token'] = Crypt::decryptString($config['access_token']);
                }

                return $config;
            },
            set: function (?array $value) {
                if (! $value) {
                    return null;
                }

                // Encrypt sensitive fields
                if (isset($value['api_key'])) {
                    $value['api_key'] = Crypt::encryptString($value['api_key']);
                }
                if (isset($value['access_token'])) {
                    $value['access_token'] = Crypt::encryptString($value['access_token']);
                }

                return json_encode($value);
            }
        );
    }

    /**
     * Check if using 360Dialog provider.
     */
    public function is360Dialog(): bool
    {
        return $this->provider === '360dialog';
    }

    /**
     * Check if using WhatsApp Cloud API provider.
     */
    public function isCloudApi(): bool
    {
        return $this->provider === 'whatsapp_cloud';
    }

    /**
     * Check if using Evolution API provider.
     */
    public function isEvolutionApi(): bool
    {
        return $this->provider === 'evolution_api';
    }

    /**
     * Get the webhook URL for this WhatsApp channel.
     */
    public function getWebhookUrl(): string
    {
        return url('/api/webhooks/whatsapp');
    }

    /**
     * Get provider API key/token.
     */
    public function getApiCredential(): ?string
    {
        $config = $this->provider_config;

        if ($this->is360Dialog()) {
            return $config['api_key'] ?? null;
        }

        if ($this->isCloudApi()) {
            return $config['access_token'] ?? null;
        }

        if ($this->isEvolutionApi()) {
            return $config['api_key'] ?? null;
        }

        return null;
    }

    /**
     * Get phone number ID (for WhatsApp Cloud API).
     */
    public function getPhoneNumberId(): ?string
    {
        if (! $this->isCloudApi()) {
            return null;
        }

        return $this->provider_config['phone_number_id'] ?? null;
    }

    /**
     * Sync message templates from provider.
     */
    public function syncTemplates(array $templates): void
    {
        $this->update(['message_templates' => $templates]);
    }

    /**
     * Get a specific message template by name.
     */
    public function getTemplate(string $name): ?array
    {
        $templates = $this->message_templates ?? [];

        foreach ($templates as $template) {
            if (($template['name'] ?? '') === $name) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Get business account ID (for WhatsApp Cloud API).
     */
    public function getBusinessAccountId(): ?string
    {
        if (! $this->isCloudApi()) {
            return null;
        }

        return $this->provider_config['business_account_id'] ?? null;
    }

    /**
     * Get client ID (for 360Dialog).
     */
    public function getClientId(): ?string
    {
        if (! $this->is360Dialog()) {
            return null;
        }

        return $this->provider_config['client_id'] ?? null;
    }

    /**
     * Get Evolution API instance name.
     */
    public function getInstanceName(): ?string
    {
        if (! $this->isEvolutionApi()) {
            return null;
        }

        return $this->provider_config['instance_name'] ?? null;
    }

    /**
     * Get Evolution API base URL.
     */
    public function getApiUrl(): ?string
    {
        if (! $this->isEvolutionApi()) {
            return null;
        }

        return $this->provider_config['api_url'] ?? null;
    }
}
