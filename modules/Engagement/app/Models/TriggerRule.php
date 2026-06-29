<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Engagement\Database\Factories\TriggerRuleFactory;
use Modules\Helpdesk\Models\Inbox;

class TriggerRule extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'engagement_trigger_rules';

    protected $fillable = [
        'inbox_id',
        'name',
        'description',
        'conditions',
        'action',
        'priority',
        'fires_per_session',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'action' => 'array',
            'priority' => 'integer',
            'fires_per_session' => 'integer',
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('id');
    }

    protected static function newFactory(): TriggerRuleFactory
    {
        return TriggerRuleFactory::new();
    }
}
