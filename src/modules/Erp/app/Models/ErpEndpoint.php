<?php

namespace Modules\Erp\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ErpEndpoint extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Mass-assignable from admin UI / request input.
     *
     * Discovery-managed columns (`controller`, `action`, `parameters`, `response_example`,
     * `is_auto_discovered`, `deprecated_at`) are intentionally excluded: only the
     * EndpointDiscoveryService should touch them. Use ->forceFill([...])->save()
     * when setting them from service code.
     */
    protected $fillable = [
        'account_id',
        'credential_id',
        'name',
        'slug',
        'url',
        'method',
        'is_active',
        'timeout',
        'retry_attempts',
        'description',
        'headers',
        'query_params',
        'content_type',
        'rate_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
        'query_params' => 'array',
        'timeout' => 'integer',
        'retry_attempts' => 'integer',
        'rate_limit' => 'integer',
        'is_auto_discovered' => 'boolean',
        'parameters' => 'array',
        'response_example' => 'array',
        'deprecated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($endpoint) {
            if (empty($endpoint->slug)) {
                $endpoint->slug = Str::slug($endpoint->name);
            }
        });
    }

    /**
     * Get the selected credential for this endpoint
     */
    public function credential()
    {
        return $this->belongsTo(ErpCredential::class, 'credential_id');
    }

    /**
     * Get all credentials for this endpoint
     */
    public function credentials()
    {
        return $this->hasMany(ErpCredential::class, 'endpoint_id');
    }

    /**
     * Get all logs for this endpoint
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ErpEndpointLog::class, 'endpoint_id');
    }

    /**
     * Get all tokens for this endpoint
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(ErpEndpointToken::class, 'endpoint_id');
    }

    /**
     * Get recent logs
     */
    public function recentLogs(int $limit = 10): HasMany
    {
        return $this->logs()->latest()->limit($limit);
    }

    /**
     * Scope to get only active endpoints
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by account
     */
    public function scopeForAccount($query, ?int $accountId)
    {
        if ($accountId) {
            return $query->where('account_id', $accountId);
        }

        return $query->whereNull('account_id');
    }

    /**
     * Scope to get only auto-discovered endpoints
     */
    public function scopeAutoDiscovered($query)
    {
        return $query->where('is_auto_discovered', true);
    }

    /**
     * Scope to get only deprecated endpoints
     */
    public function scopeDeprecated($query)
    {
        return $query->whereNotNull('deprecated_at');
    }

    /**
     * Scope to get configured endpoints (with credentials)
     */
    public function scopeConfigured($query)
    {
        return $query->whereNotNull('credential_id');
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->logs()->count();

        if ($total === 0) {
            return 0.0;
        }

        $successful = $this->logs()->where('success', true)->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average execution time in milliseconds
     */
    public function getAverageExecutionTimeAttribute(): ?int
    {
        return $this->logs()->avg('execution_time');
    }

    /**
     * Get last execution time
     */
    public function getLastExecutionAttribute(): ?ErpEndpointLog
    {
        return $this->logs()->latest()->first();
    }

    /**
     * Check if endpoint has valid credentials
     */
    public function hasValidCredentials(): bool
    {
        if (! $this->credential_id || ! $this->credential) {
            return false;
        }

        if (! $this->credential->is_active) {
            return false;
        }

        // Check if credential has expired
        if ($this->credential->expires_at && $this->credential->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get full URL with query params
     */
    public function getFullUrlAttribute(): string
    {
        $url = $this->url;

        if (! empty($this->query_params)) {
            $query = http_build_query($this->query_params);
            $url .= (str_contains($url, '?') ? '&' : '?').$query;
        }

        return $url;
    }
}
