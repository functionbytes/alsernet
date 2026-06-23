<?php

namespace Modules\HelpdeskSla\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Helpdesk\Models\Conversation;

class ConversationSlaBreach extends Model
{
    public const TYPE_FIRST_RESPONSE = 'first_response';

    public const TYPE_RESOLUTION = 'resolution';

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_conversation_sla_breaches';

    protected $fillable = [
        'conversation_id',
        'sla_type',
        'due_at',
        'breached_at',
        'minutes_over',
        'resolved',
        'resolved_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'breached_at' => 'datetime',
            'resolved_at' => 'datetime',
            'minutes_over' => 'integer',
            'resolved' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('resolved', false);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('sla_type', $type);
    }

    public function markAsResolved(): self
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->sla_type) {
            self::TYPE_FIRST_RESPONSE => __('helpdesksla::messages.breach_first_response'),
            self::TYPE_RESOLUTION => __('helpdesksla::messages.breach_resolution'),
            default => $this->sla_type,
        };
    }
}
