<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgentFlow extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_flows';

    protected function casts(): array
    {
        return [
            'nodes' => 'array',
            'edges' => 'array',
            'metadata' => 'array',
            'published_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'ai_agent_id',
        'name',
        'description',
        'trigger',
        'trigger_conditions',
        'status',
        'nodes',
        'edges',
        'metadata',
        'published_at',
    ];

    // ==================== Relationships ====================

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function flowNodes(): HasMany
    {
        return $this->hasMany(AiAgentFlowNode::class, 'flow_id');
    }

    // ==================== Scopes ====================

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeByTrigger($query, $trigger)
    {
        return $query->where('trigger', $trigger);
    }

    // ==================== Accessors ====================

    protected function triggerLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->trigger) {
                'message' => 'Mensaje',
                'intent' => 'Intención',
                'keyword' => 'Palabra clave',
                'conversation_start' => 'Inicio de conversación',
                default => $this->trigger,
            },
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'draft' => 'Borrador',
                'published' => 'Publicado',
                'archived' => 'Archivado',
                default => $this->status,
            },
        );
    }

    protected function nodeCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => count($this->nodes ?? []),
        );
    }

    protected function edgeCount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => count($this->edges ?? []),
        );
    }

    // ==================== Methods ====================

    public function publish(): static
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $this;
    }

    public function archive(): static
    {
        $this->update(['status' => 'archived']);

        return $this;
    }

    public function getNodesByType(string $type): array
    {
        return array_filter($this->nodes ?? [], fn ($node) => $node['type'] === $type);
    }

    public function getStartingNode(): ?array
    {
        $nodeIds = array_column($this->nodes ?? [], 'id');
        $targetIds = array_column($this->edges ?? [], 'target');
        $startingNodeId = array_diff($nodeIds, $targetIds)[0] ?? null;

        if ($startingNodeId) {
            return array_find($this->nodes ?? [], fn ($node) => $node['id'] === $startingNodeId);
        }

        return $this->nodes[0] ?? null;
    }
}
