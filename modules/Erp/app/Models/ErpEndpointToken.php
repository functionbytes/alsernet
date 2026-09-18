<?php

namespace Modules\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ErpEndpointToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'endpoint_id',
        'token',
        'name',
        'description',
        'expires_at',
        'usage_limit',
        'usage_count',
        'is_active',
        'allowed_ips',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'allowed_ips' => 'array',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Boot method to auto-generate token
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($token) {
            if (empty($token->token)) {
                $token->token = bin2hex(random_bytes(32));
            }
        });
    }

    /**
     * Get the endpoint this token belongs to
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(ErpEndpoint::class, 'endpoint_id');
    }

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if token has reached usage limit
     */
    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit && $this->usage_count >= $this->usage_limit;
    }

    /**
     * Check if IP is allowed
     */
    public function isIpAllowed(?string $ip = null): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        if (! $ip) {
            $ip = request()->ip();
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if token is valid for use
     */
    public function isValid(?string $ip = null): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && ! $this->hasReachedUsageLimit()
            && $this->isIpAllowed($ip);
    }

    /**
     * Increment usage count and update last_used_at in a single UPDATE
     * (avoids the 2-query/contention issue of increment() + update()).
     */
    public function recordUsage(): void
    {
        $now = now();

        $this->newQuery()
            ->whereKey($this->getKey())
            ->update([
                'usage_count' => DB::raw('usage_count + 1'),
                'last_used_at' => $now,
            ]);

        // Keep in-memory model in sync without an extra read
        $this->usage_count = (int) $this->usage_count + 1;
        $this->last_used_at = $now;
    }

    /**
     * Scope to get only active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get expired tokens
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expires_at', '<=', now())
                ->orWhere('is_active', false);
        });
    }
}
