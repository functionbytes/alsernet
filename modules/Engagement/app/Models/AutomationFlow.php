<?php

namespace Modules\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Helpdesk\Models\Inbox;

class AutomationFlow extends Model
{
    use HasFactory;

    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_EVENT = 'event';

    public const TRIGGER_SCHEDULE = 'schedule';

    protected $connection = 'helpdesk';

    protected $table = 'engagement_automation_flows';

    protected $fillable = [
        'inbox_id',
        'name',
        'description',
        'trigger_type',
        'trigger_event',
        'nodes',
        'edges',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nodes' => 'array',
            'edges' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(Inbox::class, 'inbox_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class, 'flow_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForInbox(Builder $query, int $inboxId): Builder
    {
        return $query->where('inbox_id', $inboxId);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query
            ->where('trigger_type', self::TRIGGER_EVENT)
            ->where('trigger_event', $event);
    }

    public function getStartNode(): ?array
    {
        $nodes = $this->nodes ?? [];

        return $nodes[0] ?? null;
    }

    public function findNode(string $nodeId): ?array
    {
        foreach (($this->nodes ?? []) as $node) {
            if (($node['id'] ?? null) === $nodeId) {
                return $node;
            }
        }

        return null;
    }

    public function getNextNodeId(string $fromNodeId, ?string $branchKey = null): ?string
    {
        foreach (($this->edges ?? []) as $edge) {
            if (($edge['from'] ?? null) !== $fromNodeId) {
                continue;
            }

            if ($branchKey !== null && ($edge['branch'] ?? null) !== $branchKey) {
                continue;
            }

            return $edge['to'] ?? null;
        }

        return null;
    }
}
