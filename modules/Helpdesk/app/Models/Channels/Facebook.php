<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Facebook extends Model
{
    protected $connection = 'helpdesk';

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

    protected $hidden = [
        'page_access_token',
        'user_access_token',
    ];

    protected function casts(): array
    {
        return [
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

    public function hasInstagram(): bool
    {
        return ! empty($this->instagram_id);
    }
}
