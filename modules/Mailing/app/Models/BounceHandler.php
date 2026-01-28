<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class BounceHandler extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_bounce_handlers';

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
        'protocol',
        'encryption',
        'username',
        'password',
        'email',
        'webhook_token',
        'webhook_secret',
        'rules',
        'auto_check',
        'check_interval',
        'delete_after_process',
        'auto_unsubscribe_hard_bounce',
        'soft_bounce_limit',
        'bounces_processed',
        'hard_bounces',
        'soft_bounces',
        'last_checked_at',
        'last_bounce_at',
        'last_error',
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
            'rules' => 'json',
            'auto_check' => 'boolean',
            'delete_after_process' => 'boolean',
            'auto_unsubscribe_hard_bounce' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_bounce_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this bounce handler.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get all bounce logs for this handler.
     */
    public function bounceLogs(): HasMany
    {
        return $this->hasMany(BounceLog::class, 'bounce_handler_id');
    }

    /**
     * Get all sending servers using this bounce handler.
     */
    public function sendingServers(): HasMany
    {
        return $this->hasMany(SendingServer::class, 'bounce_handler_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Process bounces from the handler.
     */
    public function processBounces(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        try {
            $this->update(['last_checked_at' => now()]);

            return true;
        } catch (\Exception $e) {
            $this->update([
                'last_error' => $e->getMessage(),
                'status' => 'error',
            ]);

            return false;
        }
    }

    /**
     * Record a bounce event.
     */
    public function recordBounce(string $email, string $bounceType, ?string $reason = null): BounceLog
    {
        return $this->bounceLogs()->create([
            'email' => $email,
            'bounce_type' => $bounceType,
            'reason' => $reason,
        ]);
    }

    /**
     * Get the display name for the handler type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'imap' => 'IMAP',
            'pop3' => 'POP3',
            'webhook' => 'Webhook',
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

    /**
     * Check if handler is due for processing based on check interval.
     */
    public function isDueForCheck(): bool
    {
        if (! $this->auto_check) {
            return false;
        }

        if ($this->last_checked_at === null) {
            return true;
        }

        return now()->diffInMinutes($this->last_checked_at) >= $this->check_interval;
    }
}
