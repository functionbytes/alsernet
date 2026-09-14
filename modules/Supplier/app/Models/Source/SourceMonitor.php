<?php

namespace Modules\Supplier\Models\Source;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Source\SourceMonitorFactory;

class SourceMonitor extends Model
{
    use HasFactory, HasUid;

    protected $table = 'supplier_source_monitors';

    protected $fillable = [
        'source_id',
        'status',
        'status_message',
        'status_code',
        'uptime_percentage',
        'avg_response_time_ms',
        'last_successful_check_at',
        'last_failed_check_at',
        'consecutive_failures',
        'consecutive_successes',
        'structure_hash',
        'structure_changed_at',
        'content_hash',
        'content_changed_at',
        'check_interval_minutes',
        'health_check_url',
        'expected_content_selector',
        'alert_on_failure',
        'alert_on_structure_change',
        'alert_sent_at',
        'snooze_alerts_until',
    ];

    protected function casts(): array
    {
        return [
            'uptime_percentage' => 'decimal:2',
            'avg_response_time_ms' => 'integer',
            'last_successful_check_at' => 'datetime',
            'last_failed_check_at' => 'datetime',
            'consecutive_failures' => 'integer',
            'consecutive_successes' => 'integer',
            'structure_changed_at' => 'datetime',
            'content_changed_at' => 'datetime',
            'check_interval_minutes' => 'integer',
            'alert_on_failure' => 'boolean',
            'alert_on_structure_change' => 'boolean',
            'alert_sent_at' => 'datetime',
            'snooze_alerts_until' => 'datetime',
        ];
    }

    protected static function newFactory(): SourceMonitorFactory
    {
        return SourceMonitorFactory::new();
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function healthHistory(): HasMany
    {
        return $this->hasMany(SourceHealthHistory::class, 'source_id', 'source_id');
    }

    public function scopeHealthy($query)
    {
        return $query->where('status', 'healthy');
    }

    public function scopeDegraded($query)
    {
        return $query->where('status', 'degraded');
    }

    public function scopeUnhealthy($query)
    {
        return $query->where('status', 'unhealthy');
    }

    public function scopeUnreachable($query)
    {
        return $query->where('status', 'unreachable');
    }

    public function scopeAlertsEnabled($query)
    {
        return $query->where('alert_on_failure', true);
    }

    public function scopeAlertsNotSnoozed($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('snooze_alerts_until')
                ->orWhere('snooze_alerts_until', '<', now());
        });
    }

    public function isHealthy(): bool
    {
        return $this->status === 'healthy';
    }

    public function isDegraded(): bool
    {
        return $this->status === 'degraded';
    }

    public function isUnhealthy(): bool
    {
        return $this->status === 'unhealthy';
    }

    public function isUnreachable(): bool
    {
        return $this->status === 'unreachable';
    }

    public function shouldSendAlert(): bool
    {
        if (! $this->alert_on_failure) {
            return false;
        }

        if ($this->snooze_alerts_until && $this->snooze_alerts_until->isFuture()) {
            return false;
        }

        return true;
    }

    public function recordSuccessfulCheck(int $responseTimeMs): void
    {
        $this->update([
            'status' => 'healthy',
            'status_message' => null,
            'status_code' => 200,
            'last_successful_check_at' => now(),
            'consecutive_successes' => $this->consecutive_successes + 1,
            'consecutive_failures' => 0,
            'avg_response_time_ms' => $this->calculateAverageResponseTime($responseTimeMs),
        ]);

        $this->updateUptimePercentage();
    }

    public function recordFailedCheck(int $statusCode, string $message): void
    {
        $consecutiveFailures = $this->consecutive_failures + 1;

        $status = match (true) {
            $consecutiveFailures >= 5 => 'unreachable',
            $consecutiveFailures >= 3 => 'unhealthy',
            default => 'degraded'
        };

        $this->update([
            'status' => $status,
            'status_message' => $message,
            'status_code' => $statusCode,
            'last_failed_check_at' => now(),
            'consecutive_failures' => $consecutiveFailures,
            'consecutive_successes' => 0,
        ]);

        $this->updateUptimePercentage();
    }

    public function recordStructureChange(string $newHash): void
    {
        $this->update([
            'structure_hash' => $newHash,
            'structure_changed_at' => now(),
        ]);
    }

    public function recordContentChange(string $newHash): void
    {
        $this->update([
            'content_hash' => $newHash,
            'content_changed_at' => now(),
        ]);
    }

    public function snoozeAlerts(int $minutes): void
    {
        $this->update([
            'snooze_alerts_until' => now()->addMinutes($minutes),
        ]);
    }

    public function markAlertSent(): void
    {
        $this->update([
            'alert_sent_at' => now(),
        ]);
    }

    protected function calculateAverageResponseTime(int $newResponseTime): int
    {
        if (! $this->avg_response_time_ms) {
            return $newResponseTime;
        }

        // Simple moving average
        return (int) (($this->avg_response_time_ms * 0.8) + ($newResponseTime * 0.2));
    }

    protected function updateUptimePercentage(): void
    {
        // Calculate uptime from last 24 hours of health history
        $uptime = $this->healthHistory()
            ->where('checked_at', '>=', now()->subDay())
            ->selectRaw('(COUNT(CASE WHEN is_success = true THEN 1 END) * 100.0 / COUNT(*)) as uptime')
            ->value('uptime');

        if ($uptime !== null) {
            $this->update(['uptime_percentage' => round($uptime, 2)]);
        }
    }
}
