<?php

namespace Modules\HelpdeskChat\Models\Channels;

use App\Models\Account;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Instagram extends Model
{
    protected $table = 'helpdesk_channel_instagrams';

    protected $fillable = [
        'account_id',
        'instagram_id',
        'username',
        'user_access_token',
        'page_access_token',
        'token_expires_at',
        'facebook_page_id',
        'additional_attributes',
    ];

    protected $casts = [
        'additional_attributes' => 'array',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'user_access_token',
        'page_access_token',
    ];

    /**
     * Get the account that owns the Instagram account.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the linked Facebook page.
     */
    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(Facebook::class);
    }

    /**
     * Get the inbox for this channel (polymorphic).
     */
    public function inbox(): MorphOne
    {
        return $this->morphOne(Inbox::class, 'channel');
    }

    /**
     * Encrypt/decrypt user access token.
     */
    protected function userAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypt/decrypt page access token.
     */
    protected function pageAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Check if access token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if access token needs refresh (expires in less than 7 days).
     */
    public function needsTokenRefresh(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->diffInDays(now()) < 7;
    }

    /**
     * Get the webhook URL for this Instagram account.
     */
    public function getWebhookUrl(): string
    {
        return url('/webhooks/instagram');
    }

    /**
     * Check if this Instagram account has a linked Facebook page.
     */
    public function hasLinkedFacebookPage(): bool
    {
        return ! empty($this->facebook_page_id);
    }
}
