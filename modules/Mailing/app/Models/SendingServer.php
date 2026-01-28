<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class SendingServer extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_sending_servers';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'user_id',
        'name',
        'type',
        'status',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'api_key',
        'api_secret',
        'api_region',
        'from_email',
        'from_name',
        'reply_to_email',
        'bounce_handler_id',
        'feedback_loop_handler_id',
        'quota_value',
        'quota_base',
        'quota_unit',
        'sending_limit_per_minute',
        'sending_limit_per_hour',
        'emails_sent_today',
        'emails_sent_this_month',
        'total_emails_sent',
        'bounce_rate',
        'complaint_rate',
        'options',
        'allow_verify_domain',
        'allow_verify_email',
        'allow_custom_return_path',
        'last_connection_check_at',
        'last_error',
        'last_sent_at',
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
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'options' => 'json',
            'allow_verify_domain' => 'boolean',
            'allow_verify_email' => 'boolean',
            'allow_custom_return_path' => 'boolean',
            'last_connection_check_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'bounce_rate' => 'decimal:2',
            'complaint_rate' => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this sending server.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the bounce handler associated with this server.
     */
    public function bounceHandler(): BelongsTo
    {
        return $this->belongsTo(BounceHandler::class, 'bounce_handler_id');
    }

    /**
     * Get the feedback loop handler associated with this server.
     */
    public function feedbackLoopHandler(): BelongsTo
    {
        return $this->belongsTo(FeedbackLoopHandler::class, 'feedback_loop_handler_id');
    }

    /**
     * Get all sub-accounts for this sending server.
     */
    public function subAccounts(): HasMany
    {
        return $this->hasMany(SubAccount::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the server is able to send emails.
     */
    public function canSend(): bool
    {
        return $this->status === 'active' && ! $this->isQuotaExceeded();
    }

    /**
     * Check if the sending quota is exceeded.
     */
    public function isQuotaExceeded(): bool
    {
        if ($this->quota_value === 0) {
            return false; // Unlimited quota
        }

        return match ($this->quota_unit) {
            'minute' => $this->emails_sent_today >= $this->quota_value,
            'hour' => $this->emails_sent_today >= $this->quota_value,
            'day' => $this->emails_sent_today >= $this->quota_value,
            default => false,
        };
    }

    /**
     * Increment the sent count for this server.
     */
    public function incrementSentCount(int $count = 1): void
    {
        $this->increment('emails_sent_today', $count);
        $this->increment('emails_sent_this_month', $count);
        $this->increment('total_emails_sent', $count);
        $this->update(['last_sent_at' => now()]);
    }

    /**
     * Reset daily counters (should be called via scheduled task).
     */
    public function resetDailyCounters(): void
    {
        $this->update(['emails_sent_today' => 0]);
    }

    /**
     * Get the display name for the server type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'smtp' => 'SMTP',
            'sendgrid' => 'SendGrid',
            'mailgun' => 'Mailgun',
            'ses' => 'AWS SES',
            'postmark' => 'Postmark',
            'sparkpost' => 'SparkPost',
            'mailjet' => 'Mailjet',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the display name for the status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'error' => 'Error',
            default => ucfirst($this->status),
        };
    }
}
