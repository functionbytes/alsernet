<?php

namespace Modules\Mailing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class MailerSetting extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_mailer_settings';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'mailer_type',
        'host',
        'port',
        'username',
        'password',
        'from_email',
        'from_name',
        'reply_to',
        'encryption',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'port' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this mailer setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the from email address for this mailer.
     */
    public function getFromEmail(): string
    {
        return $this->from_email ?? config('mail.from.address');
    }

    /**
     * Get the from name for this mailer.
     */
    public function getFromName(): string
    {
        return $this->from_name ?? config('mail.from.name');
    }

    /**
     * Get the reply to email address.
     */
    public function getReplyTo(): ?string
    {
        return $this->reply_to;
    }

    /**
     * Get the mailer configuration as an array.
     */
    public function getMailerConfig(): array
    {
        return [
            'driver' => $this->mailer_type,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from' => [
                'address' => $this->getFromEmail(),
                'name' => $this->getFromName(),
            ],
        ];
    }

    /**
     * Check if this mailer setting is active.
     */
    public function isActive(): bool
    {
        return $this->is_active ?? false;
    }

    /**
     * Mark this mailer setting as active.
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Mark this mailer setting as inactive.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }
}
