<?php

namespace Modules\Supplier\Models\Automation;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Modules\Supplier\Database\Factories\Automation\AutomationAlertFactory;

class AutomationAlert extends Model
{
    use HasFactory;

    protected $table = 'supplier_automation_alerts';

    protected $fillable = [
        'uid',
        'alert_type',
        'severity',
        'title',
        'message',
        'context',
        'related_type',
        'related_id',
        'acknowledged_by',
        'acknowledged_at',
        'resolved_at',
    ];

    protected $casts = [
        'context' => 'json',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AutomationAlert $alert): void {
            if (! $alert->uid) {
                $alert->uid = (string) Str::ulid();
            }
        });
    }

    protected static function newFactory(): AutomationAlertFactory
    {
        return AutomationAlertFactory::new();
    }

    /**
     * Get the related model (polymorphic)
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relationship to acknowledger user
     */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope: Filter by alert type
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope: Filter by severity
     */
    public function scopeWithSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: Get unacknowledged alerts
     */
    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * Scope: Get acknowledged alerts
     */
    public function scopeAcknowledged(Builder $query): Builder
    {
        return $query->whereNotNull('acknowledged_at');
    }

    /**
     * Scope: Get unresolved alerts
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope: Get resolved alerts
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope: Get critical alerts
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope: Get error alerts
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('severity', 'error');
    }

    /**
     * Scope: Get warning alerts
     */
    public function scopeWarnings(Builder $query): Builder
    {
        return $query->where('severity', 'warning');
    }

    /**
     * Scope: Get recent alerts
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Get active alerts (unacknowledged and unresolved)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('acknowledged_at')
            ->whereNull('resolved_at');
    }

    /**
     * Check if alert is acknowledged
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Check if alert is resolved
     */
    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Check if alert is active
     */
    public function isActive(): bool
    {
        return ! $this->isAcknowledged() && ! $this->isResolved();
    }

    /**
     * Acknowledge alert
     */
    public function acknowledge(?int $userId = null): void
    {
        $this->update([
            'acknowledged_by' => $userId ?? auth()->id(),
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Resolve alert
     */
    public function resolve(): void
    {
        $updates = ['resolved_at' => now()];

        if (! $this->acknowledged_at) {
            $updates['acknowledged_by'] = auth()->id();
            $updates['acknowledged_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * Create an alert
     */
    public static function createAlert(
        string $alertType,
        string $severity,
        string $title,
        string $message,
        ?array $context = null,
        ?Model $relatedModel = null
    ): self {
        $data = [
            'alert_type' => $alertType,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'context' => $context,
        ];

        if ($relatedModel) {
            $data['related_type'] = get_class($relatedModel);
            $data['related_id'] = $relatedModel->id;
        }

        return self::create($data);
    }

    /**
     * Check if similar alert exists recently
     */
    public static function hasSimilarRecentAlert(
        string $alertType,
        ?Model $relatedModel = null,
        int $minutes = 30
    ): bool {
        $query = self::where('alert_type', $alertType)
            ->where('created_at', '>=', now()->subMinutes($minutes));

        if ($relatedModel) {
            $query->where('related_type', get_class($relatedModel))
                ->where('related_id', $relatedModel->id);
        }

        return $query->exists();
    }

    /**
     * Create alert with throttling
     */
    public static function createThrottled(
        string $alertType,
        string $severity,
        string $title,
        string $message,
        ?array $context = null,
        ?Model $relatedModel = null,
        int $throttleMinutes = 30
    ): ?self {
        // Check if similar alert exists recently
        if (self::hasSimilarRecentAlert($alertType, $relatedModel, $throttleMinutes)) {
            return null;
        }

        return self::createAlert($alertType, $severity, $title, $message, $context, $relatedModel);
    }

    /**
     * Get alert statistics
     */
    public static function getStatistics(int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $alerts = self::where('created_at', '>=', $since)->get();

        return [
            'total' => $alerts->count(),
            'active' => $alerts->filter(fn ($a) => $a->isActive())->count(),
            'acknowledged' => $alerts->whereNotNull('acknowledged_at')->count(),
            'resolved' => $alerts->whereNotNull('resolved_at')->count(),
            'by_severity' => [
                'critical' => $alerts->where('severity', 'critical')->count(),
                'error' => $alerts->where('severity', 'error')->count(),
                'warning' => $alerts->where('severity', 'warning')->count(),
                'info' => $alerts->where('severity', 'info')->count(),
            ],
            'by_type' => $alerts->groupBy('alert_type')
                ->map(fn ($group) => $group->count())
                ->toArray(),
        ];
    }

    /**
     * Bulk acknowledge alerts
     */
    public static function bulkAcknowledge(array $ids, ?int $userId = null): int
    {
        return self::whereIn('id', $ids)
            ->whereNull('acknowledged_at')
            ->update([
                'acknowledged_by' => $userId ?? auth()->id(),
                'acknowledged_at' => now(),
            ]);
    }

    /**
     * Bulk resolve alerts
     */
    public static function bulkResolve(array $ids): int
    {
        return self::whereIn('id', $ids)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'acknowledged_by' => fn ($query) => $query->acknowledged_by ?? auth()->id(),
                'acknowledged_at' => fn ($query) => $query->acknowledged_at ?? now(),
            ]);
    }

    /**
     * Get alerts dashboard summary
     */
    public static function getDashboardSummary(): array
    {
        return [
            'active_critical' => self::active()->critical()->count(),
            'active_errors' => self::active()->errors()->count(),
            'active_warnings' => self::active()->warnings()->count(),
            'recent_24h' => self::recent(24)->count(),
            'unacknowledged' => self::unacknowledged()->count(),
        ];
    }
}
