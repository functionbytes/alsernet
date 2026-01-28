<?php

namespace Modules\Mailing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Mailing\Traits\HasUid;

class EmailVerificationResult extends Model
{
    use HasFactory, HasUid;

    protected $table = 'mailing_email_verification_results';

    /*
    |--------------------------------------------------------------------------
    | Fillable Attributes
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'uid',
        'server_id',
        'email',
        'result',
        'reason',
        'details',
        'is_disposable',
        'is_role_based',
        'is_catch_all',
        'smtp_valid',
        'score',
        'verified_at',
        'expires_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'details' => 'json',
            'is_disposable' => 'boolean',
            'is_role_based' => 'boolean',
            'is_catch_all' => 'boolean',
            'smtp_valid' => 'boolean',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the email verification server that owns this result.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(EmailVerificationServer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the email is valid.
     */
    public function isValid(): bool
    {
        return $this->result === 'valid';
    }

    /**
     * Check if the email is invalid.
     */
    public function isInvalid(): bool
    {
        return $this->result === 'invalid';
    }

    /**
     * Check if the email is risky.
     */
    public function isRisky(): bool
    {
        return $this->result === 'risky';
    }

    /**
     * Check if the result is unknown.
     */
    public function isUnknown(): bool
    {
        return $this->result === 'unknown';
    }

    /**
     * Check if the verification result has expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the verification result is still valid (not expired).
     */
    public function isFresh(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Get the display label for the result.
     */
    public function getResultLabel(): string
    {
        return match ($this->result) {
            'valid' => 'Valid',
            'invalid' => 'Invalid',
            'risky' => 'Risky',
            'unknown' => 'Unknown',
            default => ucfirst($this->result),
        };
    }

    /**
     * Get the CSS class for result status display.
     */
    public function getResultClass(): string
    {
        return match ($this->result) {
            'valid' => 'success',
            'invalid' => 'danger',
            'risky' => 'warning',
            'unknown' => 'secondary',
            default => 'light',
        };
    }

    /**
     * Get a detailed description of the verification result.
     */
    public function getDetailedDescription(): string
    {
        $description = $this->getResultLabel();

        if ($this->is_disposable) {
            $description .= ' (Disposable)';
        }

        if ($this->is_role_based) {
            $description .= ' (Role-based)';
        }

        if ($this->is_catch_all) {
            $description .= ' (Catch-all)';
        }

        if ($this->reason) {
            $description .= ' - '.$this->reason;
        }

        return $description;
    }
}
