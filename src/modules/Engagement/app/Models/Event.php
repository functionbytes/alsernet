<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\EventFactory;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Inbox;

class Event extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_events';

    protected $fillable = [
        'session_token',
        'customer_id',
        'inbox_id',
        'event_name',
        'properties',
        'page_url',
        'page_title',
        'referrer',
        'user_agent',
        'ip_address',
        'occurred_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'occurred_at' => 'datetime',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function scopeForSession(Builder $query, string $sessionToken): Builder
    {
        return $query->where('session_token', $sessionToken);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeOfType(Builder $query, string $eventName): Builder
    {
        return $query->where('event_name', $eventName);
    }

    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}
