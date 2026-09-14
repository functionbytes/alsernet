<?php

namespace Modules\HelpdeskBirthday\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * La campaña de cumpleaños de un día concreto.
 *
 * Máquina de estados:
 *   draft ──prepare()──> scheduled ──primer envío──> sending ──> completed
 *     │                      │            │
 *     │                      └──pause()───┴──> paused ──resume()──> scheduled
 *     └──sin cupón / guarda de seguridad──> failed
 *   cualquiera ──cancel()──> cancelled
 */
class BirthdayCampaign extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /** Estados en los que dispatch-due puede sacar destinatarios. */
    public const ACTIVE_STATUSES = [self::STATUS_SCHEDULED, self::STATUS_SENDING];

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ERP = 'erp';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_birthday_campaigns';

    protected $fillable = [
        'uid',
        'campaign_date',
        'status',
        'coupon_code',
        'coupon_valid_from',
        'coupon_valid_to',
        'coupon_amount',
        'coupon_min_purchase',
        'coupon_source',
        'coupon_meta',
        'template_key',
        'window_start',
        'window_end',
        'throttle_per_hour',
        'interval_seconds',
        'recipients_total',
        'sent_count',
        'failed_count',
        'skipped_count',
        'audience_stats',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'audience_stats' => 'array',
            'campaign_date' => 'date',
            'coupon_valid_from' => 'date',
            'coupon_valid_to' => 'date',
            'coupon_amount' => 'decimal:2',
            'coupon_min_purchase' => 'decimal:2',
            'coupon_meta' => 'array',
            'throttle_per_hour' => 'integer',
            'interval_seconds' => 'integer',
            'recipients_total' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            $campaign->uid ??= (string) Str::uuid();
        });
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(BirthdayRecipient::class, 'campaign_id');
    }

    public function scopeForDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('campaign_date', $date);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Estados desde los que todavía se puede intervenir manualmente.
     */
    public function canBePaused(): bool
    {
        return $this->isActive();
    }

    public function canBeResumed(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_SENDING,
            self::STATUS_PAUSED,
        ], true);
    }

    public function pendingCount(): int
    {
        return $this->recipients()
            ->whereIn('status', [BirthdayRecipient::STATUS_PENDING, BirthdayRecipient::STATUS_SENDING])
            ->count();
    }

    /**
     * Porcentaje de avance sobre los destinatarios que sí se van a intentar
     * (los omitidos no cuentan: nunca estuvieron en la cola de envío).
     */
    public function progressPercent(): int
    {
        $target = $this->recipients_total - $this->skipped_count;

        if ($target <= 0) {
            return 100;
        }

        return (int) min(100, round((($this->sent_count + $this->failed_count) / $target) * 100));
    }
}
