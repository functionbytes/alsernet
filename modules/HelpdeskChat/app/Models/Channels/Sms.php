<?php

namespace Modules\HelpdeskChat\Models\Channels;

use App\Models\Account;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Sms extends Model
{
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

    protected $casts = [
        'active' => 'boolean',
        'additional_settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    /**
     * Get the account that owns the SMS channel.
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
     * Encrypt/decrypt API key.
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null
        );
    }

    /**
     * Encrypt/decrypt API secret.
     */
    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null
        );
    }

    /**
     * Get formatted phone number for display.
     */
    public function getFormattedPhoneNumberAttribute(): string
    {
        // Format E.164 number for display
        // Example: +12345678901 -> +1 (234) 567-8901
        $number = $this->phone_number;

        if (preg_match('/^\+1(\d{3})(\d{3})(\d{4})$/', $number, $matches)) {
            return "+1 ({$matches[1]}) {$matches[2]}-{$matches[3]}";
        }

        return $number;
    }

    /**
     * Check if channel is active and properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->active
            && ! empty($this->phone_number)
            && ! empty($this->api_key)
            && ! empty($this->api_secret);
    }

    /**
     * Get provider display name.
     */
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
