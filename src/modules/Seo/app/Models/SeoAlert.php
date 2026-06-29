<?php

namespace Modules\Seo\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SeoAlert extends Model
{
    protected $table = 'seo_alerts';

    public const TYPE_SCORE_DROP = 'score_drop';

    public const TYPE_NEW_404 = 'new_404';

    public const TYPE_REDIRECT_CHAIN = 'redirect_chain';

    public const TYPE_WEBHOOK_FAIL = 'webhook_fail';

    public const TYPE_BROKEN_LINK = 'broken_link';

    public const TYPE_VITAL_POOR = 'vital_poor';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'type', 'severity', 'title', 'message', 'context',
        'url', 'acknowledged_at', 'acknowledged_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'context' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Factory-like helper to persist a new alert with consistent defaults.
     * Deduplicates within 24h by (type, url) so the same issue doesn't spam
     * the timeline.
     */
    public static function raise(
        string $type,
        string $severity,
        string $title,
        ?string $message = null,
        ?string $url = null,
        array $context = []
    ): self {
        $existing = static::query()
            ->where('type', $type)
            ->where('url', $url)
            ->whereNull('acknowledged_at')
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            $existing->update([
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'context' => array_merge((array) $existing->context, $context),
            ]);

            return $existing;
        }

        return static::create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'context' => $context,
        ]);
    }

    public function scopeUnacknowledged(Builder $query): Builder
    {
        return $query->whereNull('acknowledged_at');
    }

    public function scopeOfSeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function acknowledge(?int $userId = null): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }
}
