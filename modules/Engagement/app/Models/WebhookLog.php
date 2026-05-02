<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DEAD = 'dead';

    public const MAX_ATTEMPTS = 5;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_webhook_logs';

    protected $fillable = [
        'platform_integration_id',
        'platform',
        'topic',
        'payload',
        'status',
        'attempts',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(PlatformIntegration::class, 'platform_integration_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_RECEIVED, self::STATUS_FAILED])
            ->where('attempts', '<', self::MAX_ATTEMPTS);
    }

    public function scopeDead(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DEAD);
    }
}
