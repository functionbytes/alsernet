<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class FeedbackLoopHandler extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_feedback_loop_handlers';

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
        'provider',
        'feedback_type',
        'rules',
        'auto_check',
        'check_interval',
        'delete_after_process',
        'auto_unsubscribe',
        'notify_admin',
        'complaints_processed',
        'last_checked_at',
        'last_complaint_at',
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
            'auto_unsubscribe' => 'boolean',
            'notify_admin' => 'boolean',
            'last_checked_at' => 'datetime',
            'last_complaint_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this feedback loop handler.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get all feedback logs for this handler.
     */
    public function feedbackLogs(): HasMany
    {
        return $this->hasMany(FeedbackLog::class, 'feedback_loop_handler_id');
    }

    /**
     * Get all sending servers using this feedback loop handler.
     */
    public function sendingServers(): HasMany
    {
        return $this->hasMany(SendingServer::class, 'feedback_loop_handler_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Process complaints from the handler.
     */
    public function processComplaints(): bool
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
     * Record a complaint event.
     */
    public function recordComplaint(string $email, ?string $complaintType = null, ?string $feedback = null): FeedbackLog
    {
        return $this->feedbackLogs()->create([
            'email' => $email,
            'complaint_type' => $complaintType,
            'feedback_report' => $feedback,
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
            'api' => 'API',
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
     * Get the display name for the provider.
     */
    public function getProviderLabel(): string
    {
        return match ($this->provider) {
            'gmail' => 'Gmail',
            'yahoo' => 'Yahoo',
            'aol' => 'AOL',
            'outlook' => 'Outlook',
            'custom' => 'Custom',
            default => ucfirst($this->provider ?? 'Unknown'),
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
