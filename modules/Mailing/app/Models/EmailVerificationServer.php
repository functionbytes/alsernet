<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Mailing\Traits\HasUid;

class EmailVerificationServer extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'mailing_email_verification_servers';

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
        'api_key',
        'api_url',
        'api_options',
        'check_disposable',
        'check_role_based',
        'check_smtp',
        'check_catch_all',
        'requests_per_minute',
        'requests_per_day',
        'verifications_today',
        'verifications_this_month',
        'total_verifications',
        'credits_available',
        'credits_used',
        'credit_threshold',
        'credits_last_checked_at',
        'valid_count',
        'invalid_count',
        'risky_count',
        'unknown_count',
        'last_verification_at',
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
            'api_key' => 'encrypted',
            'api_options' => 'json',
            'check_disposable' => 'boolean',
            'check_role_based' => 'boolean',
            'check_smtp' => 'boolean',
            'check_catch_all' => 'boolean',
            'credits_last_checked_at' => 'datetime',
            'last_verification_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this email verification server.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get all verification results for this server.
     */
    public function verificationResults(): HasMany
    {
        return $this->hasMany(EmailVerificationResult::class, 'server_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Verify an email address.
     */
    public function verifyEmail(string $email): ?EmailVerificationResult
    {
        // Check if result already exists and is still valid
        $cached = $this->verificationResults()
            ->where('email', $email)
            ->where('expires_at', '>', now())
            ->first();

        if ($cached) {
            return $cached;
        }

        // Implement verification logic here
        // This would call the actual API service
        return null;
    }

    /**
     * Check available credits.
     */
    public function checkCredits(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->credits_available === null) {
            return true; // Unlimited credits
        }

        return $this->credits_available > 0;
    }

    /**
     * Check if credit warning threshold is reached.
     */
    public function isLowOnCredits(): bool
    {
        if ($this->credits_available === null) {
            return false; // Unlimited credits
        }

        $creditPercentage = ($this->credits_available / ($this->credits_available + $this->credits_used)) * 100;

        return $creditPercentage <= $this->credit_threshold;
    }

    /**
     * Check if daily request limit is exceeded.
     */
    public function isDailyLimitExceeded(): bool
    {
        return $this->verifications_today >= $this->requests_per_day;
    }

    /**
     * Check if minute request limit is exceeded.
     */
    public function isMinuteLimitExceeded(): bool
    {
        $minuteAgo = now()->subMinute();
        $recentVerifications = $this->verificationResults()
            ->where('verified_at', '>=', $minuteAgo)
            ->count();

        return $recentVerifications >= $this->requests_per_minute;
    }

    /**
     * Increment verification counters.
     */
    public function incrementVerificationCount(): void
    {
        $this->increment('verifications_today');
        $this->increment('verifications_this_month');
        $this->increment('total_verifications');
        $this->update(['last_verification_at' => now()]);
    }

    /**
     * Reset daily counters (should be called via scheduled task).
     */
    public function resetDailyCounters(): void
    {
        $this->update(['verifications_today' => 0]);
    }

    /**
     * Update result statistics.
     */
    public function updateResultStatistics(): void
    {
        $results = $this->verificationResults()->get();

        $this->update([
            'valid_count' => $results->where('result', 'valid')->count(),
            'invalid_count' => $results->where('result', 'invalid')->count(),
            'risky_count' => $results->where('result', 'risky')->count(),
            'unknown_count' => $results->where('result', 'unknown')->count(),
        ]);
    }

    /**
     * Get the display name for the server type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'zerobounce' => 'ZeroBounce',
            'neverbounce' => 'NeverBounce',
            'kickbox' => 'Kickbox',
            'clearout' => 'Clearout',
            'debounce' => 'Debounce',
            'custom' => 'Custom',
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
     * Get the validity rate percentage.
     */
    public function getValidityRate(): float
    {
        $total = $this->valid_count + $this->invalid_count + $this->risky_count + $this->unknown_count;

        if ($total === 0) {
            return 0;
        }

        return ($this->valid_count / $total) * 100;
    }
}
