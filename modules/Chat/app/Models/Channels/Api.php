<?php

namespace Modules\Chat\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Inbox\Inbox;

class Api extends Model
{
    protected $table = 'chat_channel_apis';

    protected $fillable = [
        'account_id',
        'identifier',
        'webhook_url',
        'hmac_token',
        'webhook_verify_token',
        'active',
        'additional_settings',
    ];

    protected $casts = [
        'active' => 'boolean',
        'additional_settings' => 'array',
    ];

    /**
     * Get the account that owns this API channel.
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

    /**
     * Get channel name.
     */
    public function getNameAttribute(): string
    {
        return 'API';
    }
}
