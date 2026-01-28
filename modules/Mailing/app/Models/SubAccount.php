<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class SubAccount extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_sub_accounts';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'sending_server_id',
        'user_id',
        'name',
        'description',
        'status',
        'api_key',
        'username',
        'email',
        'permissions',
        'can_create_campaigns',
        'can_manage_subscribers',
        'can_view_reports',
        'can_create_sending_servers',
        'email_quota_per_day',
        'email_quota_per_month',
        'subscriber_quota',
        'campaign_quota',
        'emails_sent_today',
        'emails_sent_this_month',
        'total_emails_sent',
        'allowed_sending_server_ids',
        'allowed_list_ids',
        'last_login_at',
        'last_activity_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'permissions' => 'json',
            'allowed_sending_server_ids' => 'json',
            'allowed_list_ids' => 'json',
            'can_create_campaigns' => 'boolean',
            'can_manage_subscribers' => 'boolean',
            'can_view_reports' => 'boolean',
            'can_create_sending_servers' => 'boolean',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this sub-account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the sending server this sub-account is associated with.
     */
    public function sendingServer(): BelongsTo
    {
        return $this->belongsTo(SendingServer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the sub-account can send emails.
     */
    public function canSendEmails(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Check daily quota
        if ($this->email_quota_per_day !== null && $this->emails_sent_today >= $this->email_quota_per_day) {
            return false;
        }

        // Check monthly quota
        if ($this->email_quota_per_month !== null && $this->emails_sent_this_month >= $this->email_quota_per_month) {
            return false;
        }

        return true;
    }

    /**
     * Check if the sub-account can access a specific sending server.
     */
    public function canAccessSendingServer(int $serverId): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // If no restrictions, can access all servers
        if ($this->allowed_sending_server_ids === null || empty($this->allowed_sending_server_ids)) {
            return true;
        }

        return in_array($serverId, $this->allowed_sending_server_ids);
    }

    /**
     * Check if the sub-account can access a specific list.
     */
    public function canAccessList(int $listId): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // If no restrictions, can access all lists
        if ($this->allowed_list_ids === null || empty($this->allowed_list_ids)) {
            return true;
        }

        return in_array($listId, $this->allowed_list_ids);
    }

    /**
     * Check if the sub-account has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions);
    }

    /**
     * Increment the sent count for this sub-account.
     */
    public function incrementSentCount(int $count = 1): void
    {
        $this->increment('emails_sent_today', $count);
        $this->increment('emails_sent_this_month', $count);
        $this->increment('total_emails_sent', $count);
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Reset daily counters (should be called via scheduled task).
     */
    public function resetDailyCounters(): void
    {
        $this->update(['emails_sent_today' => 0]);
    }

    /**
     * Get the display name for the status.
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'suspended' => 'Suspended',
            'inactive' => 'Inactive',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get remaining daily quota.
     */
    public function getRemainingDailyQuota(): ?int
    {
        if ($this->email_quota_per_day === null) {
            return null;
        }

        return max(0, $this->email_quota_per_day - $this->emails_sent_today);
    }

    /**
     * Get remaining monthly quota.
     */
    public function getRemainingMonthlyQuota(): ?int
    {
        if ($this->email_quota_per_month === null) {
            return null;
        }

        return max(0, $this->email_quota_per_month - $this->emails_sent_this_month);
    }

    /**
     * Get daily quota percentage used.
     */
    public function getDailyQuotaPercentage(): ?float
    {
        if ($this->email_quota_per_day === null) {
            return null;
        }

        return ($this->emails_sent_today / $this->email_quota_per_day) * 100;
    }

    /**
     * Get monthly quota percentage used.
     */
    public function getMonthlyQuotaPercentage(): ?float
    {
        if ($this->email_quota_per_month === null) {
            return null;
        }

        return ($this->emails_sent_this_month / $this->email_quota_per_month) * 100;
    }
}
