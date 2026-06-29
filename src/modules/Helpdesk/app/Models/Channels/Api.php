<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Api extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_channel_apis';

    protected $hidden = [
        'hmac_token',
        'webhook_verify_token',
    ];

    protected $fillable = [
        'account_id',
        'identifier',
        'webhook_url',
        'hmac_token',
        'webhook_verify_token',
        'active',
        'additional_settings',
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
     * Encrypted accessor for HMAC token.
     */
    protected function hmacToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypted accessor for webhook verify token.
     */
    protected function webhookVerifyToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }
}
