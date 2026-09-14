<?php

namespace Modules\Supplier\Models\Automation;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Database\Factories\Automation\AutomationHealthCheckFactory;

class AutomationHealthCheck extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_health_checks';

    public $timestamps = false;

    protected $fillable = [
        'check_type',
        'target_id',
        'status',
        'response_time_ms',
        'error_message',
        'metadata',
        'checked_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'checked_at' => 'datetime',
        'response_time_ms' => 'integer',
    ];

    protected static function newFactory(): AutomationHealthCheckFactory
    {
        return AutomationHealthCheckFactory::new();
    }

    /**
     * Scope: Filter by check type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('check_type', $type);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get recent checks
     */
    public function scopeRecent(Builder $query, int $minutes = 60): Builder
    {
        return $query->where('checked_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope: Get unhealthy checks
     */
    public function scopeUnhealthy(Builder $query): Builder
    {
        return $query->whereIn('status', ['unhealthy', 'degraded']);
    }

    /**
     * Check if the health check is healthy
     */
    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    /**
     * Check if the health check is unhealthy
     */
    public function isUnhealthy(): bool
    {
        return in_array($this->status, ['unhealthy', 'degraded']);
    }

    /**
     * Record a health check
     */
    public static function record(
        string $checkType,
        ?string $targetId,
        string $status,
        ?int $responseTimeMs = null,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): self {
        return self::create([
            'check_type' => $checkType,
            'target_id' => $targetId,
            'status' => $status,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'checked_at' => now(),
        ]);
    }

    /**
     * Get latest check for a specific target
     */
    public static function getLatestForTarget(string $checkType, string $targetId): ?self
    {
        return self::where('check_type', $checkType)
            ->where('target_id', $targetId)
            ->orderBy('checked_at', 'desc')
            ->first();
    }

    /**
     * Get health summary for a check type
     */
    public static function getHealthSummary(string $checkType, int $minutes = 60): array
    {
        $checks = self::where('check_type', $checkType)
            ->where('checked_at', '>=', now()->subMinutes($minutes))
            ->get();

        return [
            'total' => $checks->count(),
            'healthy' => $checks->where('status', 'healthy')->count(),
            'degraded' => $checks->where('status', 'degraded')->count(),
            'unhealthy' => $checks->where('status', 'unhealthy')->count(),
            'unknown' => $checks->where('status', 'unknown')->count(),
            'avg_response_time' => $checks->avg('response_time_ms'),
        ];
    }
}
