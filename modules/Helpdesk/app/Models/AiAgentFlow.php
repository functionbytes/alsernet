<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiAgentFlow extends Model
{
    use SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_flows';

    protected $guarded = [];

    protected $casts = [
        'nodes' => 'array', // React Flow nodes
        'edges' => 'array', // React Flow edges
        'metadata' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'ai_agent_id',
        'name',
        'description',
        'trigger', // 'message', 'intent', 'keyword', 'conversation_start'
        'status', // 'draft', 'published', 'archived'
        'nodes',
        'edges',
        'metadata',
        'published_at',
    ];

    // ==================== Relationships ====================

    /**
     * The AI agent this flow belongs to
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    /**
     * Nodes in this flow
     */
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

    /**
     * Get trigger label
     */
    public function getTriggerLabelAttribute()
    {
        return match ($this->trigger) {
            'message' => 'Mensaje',
            'intent' => 'Intención',
            'keyword' => 'Palabra clave',
            'conversation_start' => 'Inicio de conversación',
            default => $this->trigger,
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'published' => 'Publicado',
            'archived' => 'Archivado',
            default => $this->status,
        };
    }

    /**
     * Get node count
     */
    public function getNodeCountAttribute()
    {
        return count($this->nodes ?? []);
    }

    /**
     * Get edge count
     */
    public function getEdgeCountAttribute()
    {
        return count($this->edges ?? []);
    }

    // ==================== Methods ====================

    /**
     * Publish the flow
     */
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $this;
    }

    /**
     * Archive the flow
     */
    public function archive()
    {
        $this->update(['status' => 'archived']);

        return $this;
    }

    /**
     * Get nodes by type
     */
    public function getNodesByType($type)
    {
        return array_filter($this->nodes ?? [], fn ($node) => $node['type'] === $type);
    }

    /**
     * Get starting node (node with no incoming edges)
     */
    public function getStartingNode()
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
