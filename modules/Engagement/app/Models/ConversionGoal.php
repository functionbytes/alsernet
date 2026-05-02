<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Inbox;

class ConversionGoal extends Model
{
    public const TYPE_EVENT = 'event';

    public const TYPE_URL_VISIT = 'url_visit';

    public const TYPE_SEGMENT_CHANGE = 'segment_change';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_conversion_goals';

    protected $fillable = [
        'inbox_id',
        'name',
        'type',
        'event_name',
        'url_pattern',
        'target_segment',
        'value',
        'funnel_steps',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'funnel_steps' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }
}
