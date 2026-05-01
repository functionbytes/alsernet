<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Whatsapp extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_channel_whatsapps';

    protected $fillable = [
        'account_id',
        'phone_number',
        'provider',
        'provider_config',
        'webhook_url',
        'message_templates',
        'additional_attributes',
    ];

    protected function casts(): array
    {
        return [
            'message_templates' => 'array',
            'additional_attributes' => 'array',
        ];
    }

    /**
     * Get the inbox for this channel (polymorphic).
     */
    public function inbox(): MorphOne
    {
        return $this->morphOne(Inbox::class, 'channel');
    }

    /**
     * Encrypt/decrypt provider config with sensitive fields.
     */
    protected function providerConfig(): Attribute
    {
        return Attribute::make(
            get: function (?string $value) {
                if (! $value) {
                    return [];
                }

                $config = json_decode($value, true);

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

    public function is360Dialog(): bool
    {
        return $this->provider === '360dialog';
    }

    public function isCloudApi(): bool
    {
        return $this->provider === 'whatsapp_cloud';
    }

    public function isEvolutionApi(): bool
    {
        return $this->provider === 'evolution_api';
    }

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

    public function getPhoneNumberId(): ?string
    {
        return $this->isCloudApi()
            ? ($this->provider_config['phone_number_id'] ?? null)
            : null;
    }

    public function getBusinessAccountId(): ?string
    {
        return $this->isCloudApi()
            ? ($this->provider_config['business_account_id'] ?? null)
            : null;
    }

    public function getInstanceName(): ?string
    {
        return $this->isEvolutionApi()
            ? ($this->provider_config['instance_name'] ?? null)
            : null;
    }

    public function getApiUrl(): ?string
    {
        return $this->isEvolutionApi()
            ? ($this->provider_config['api_url'] ?? null)
            : null;
    }

    public function syncTemplates(array $templates): void
    {
        $this->update(['message_templates' => $templates]);
    }

    public function getTemplate(string $name): ?array
    {
        foreach ($this->message_templates ?? [] as $template) {
            if (($template['name'] ?? '') === $name) {
                return $template;
            }
        }

        return null;
    }
}
