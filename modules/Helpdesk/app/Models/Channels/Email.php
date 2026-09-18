<?php

namespace Modules\Helpdesk\Models\Channels;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Crypt;
use Modules\Helpdesk\Models\Inbox;

class Email extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_channel_emails';

    protected $fillable = [
        'account_id',
        'email',
        'forward_to_email',
        'imap_enabled',
        'imap_address',
        'imap_port',
        'imap_login',
        'imap_password',
        'imap_enable_ssl',
        'smtp_enabled',
        'smtp_address',
        'smtp_port',
        'smtp_login',
        'smtp_password',
        'smtp_domain',
        'smtp_authentication',
        'smtp_enable_starttls_auto',
        'smtp_enable_ssl_tls',
        'smtp_openssl_verify_mode',
        'provider',
        'provider_config',
        'verified_for_sending',
    ];

    protected $hidden = [
        'imap_password',
        'smtp_password',
    ];

    protected function casts(): array
    {
        return [
            'imap_enabled' => 'boolean',
            'imap_enable_ssl' => 'boolean',
            'smtp_enabled' => 'boolean',
            'smtp_enable_starttls_auto' => 'boolean',
            'smtp_enable_ssl_tls' => 'boolean',
            'verified_for_sending' => 'boolean',
            'provider_config' => 'array',
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
     * Encrypted accessor for IMAP password.
     */
    protected function imapPassword(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Encrypted accessor for SMTP password.
     */
    protected function smtpPassword(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? Crypt::decryptString($value) : null,
            set: fn (?string $value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    public function isMicrosoft(): bool
    {
        return $this->provider === 'microsoft';
    }

    public function isGoogle(): bool
    {
        return $this->provider === 'google';
    }
}
