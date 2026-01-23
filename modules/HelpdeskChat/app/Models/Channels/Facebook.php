<?php

namespace Modules\HelpdeskChat\Models\Channels;

use App\Models\Account;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\HelpdeskChat\Models\Accounts\Inbox;

class Facebook extends Model
{
    protected $table = 'helpdesk_channel_facebooks';

    protected $fillable = [
        'account_id',
        'page_id',
        'page_name',
        'page_access_token',
        'user_access_token',
        'instagram_id',
        'additional_attributes',
    ];

    protected $casts = [
        'additional_attributes' => 'array',
    ];

    protected $hidden = [
        'page_access_token',
        'user_access_token',
    ];

    /**
     * Get the account that owns the Facebook page.
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
     * Check if this page has an Instagram account linked.
     */
    public function hasInstagram(): bool
    {
        return ! empty($this->instagram_id);
    }

    /**
     * Get the webhook URL for this Facebook page.
     */
    public function getWebhookUrl(): string
    {
        return url('/webhooks/facebook');
    }

    /**
     * Get the callback URL for OAuth.
     */
    public static function getOAuthCallbackUrl(): string
    {
        return url('/admin/channels/facebook-pages/callback');
    }
}
