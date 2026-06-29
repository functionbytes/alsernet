<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EventArchive extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'engagement_events_archive';

    public $timestamps = false;

    protected $fillable = [
        'session_token',
        'inbox_id',
        'customer_id',
        'event_name',
        'platform',
        'properties',
        'occurred_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeForSession(Builder $query, string $sessionToken): Builder
    {
        return $query->where('session_token', $sessionToken);
    }
}
