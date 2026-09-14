<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Instagram extends Model
{
    protected $connection = 'helpdesk';

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

    protected $hidden = [
        'user_access_token',
        'page_access_token',
    ];

    protected function casts(): array
    {
        return [
            'additional_attributes' => 'array',
            'token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the linked Facebook page.
     */
    public function facebookPage(): BelongsTo
    {
        return $this->belongsTo(Facebook::class, 'facebook_page_id');
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

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function needsTokenRefresh(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->diffInDays(now()) < 7;
    }

    public function hasLinkedFacebookPage(): bool
    {
        return ! empty($this->facebook_page_id);
    }
}
