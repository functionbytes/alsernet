<?php

namespace Modules\Chat\Models\Ai;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentFlowNode extends Model
{
    protected $table = 'chat_ai_agent_flow_nodes';

    protected $casts = [
        'data' => 'array',
        'position' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'flow_id',
        'node_id',
        'type',
        'label',
        'data',
        'position',
        'metadata',
    ];

    // ==================== Relationships ====================

    /**
     * The flow this node belongs to
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(AiAgentFlow::class, 'flow_id');
    }

    // ==================== Scopes ====================

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByNodeId($query, string $nodeId)
    {
        return $query->where('node_id', $nodeId);
    }

    // ==================== Accessors ====================

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'input' => 'Entrada',
            'prompt' => 'Prompt',
            'condition' => 'Condición',
            'action' => 'Acción',
            'output' => 'Salida',
            default => $this->type,
        };
    }

    /**
     * Get icon based on type
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'input' => 'fa-sign-in-alt',
            'prompt' => 'fa-comments',
            'condition' => 'fa-code-branch',
            'action' => 'fa-bolt',
            'output' => 'fa-sign-out-alt',
            default => 'fa-circle',
        };
    }

    /**
     * Get color based on type
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'input' => '#b10100',
            'prompt' => '#13C672',
            'condition' => '#FEC90F',
            'action' => '#FA896B',
            'output' => '#13C672',
            default => '#6c757d',
        };
    }

    // ==================== Methods ====================

    /**
     * Get data value by key
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data value by key
     */
    public function setDataValue(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->update(['data' => $data]);
    }
}
