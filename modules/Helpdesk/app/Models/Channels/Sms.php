<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Sms extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_channel_sms';

    protected $fillable = [
        'account_id',
        'provider',
        'phone_number',
        'api_key',
        'api_secret',
        'application_id',
        'webhook_verify_token',
        'active',
        'additional_settings',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'additional_settings' => 'array',
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
     * Encrypt/decrypt API key.
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt/decrypt API secret.
     */
    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function isConfigured(): bool
    {
        return $this->active
            && ! empty($this->phone_number)
            && ! empty($this->api_key)
            && ! empty($this->api_secret);
    }

    public function getProviderDisplayNameAttribute(): string
    {
        return match ($this->provider) {
            'bandwidth' => 'Bandwidth',
            'twilio' => 'Twilio',
            'telnyx' => 'Telnyx',
            default => ucfirst($this->provider),
        };
    }
}
