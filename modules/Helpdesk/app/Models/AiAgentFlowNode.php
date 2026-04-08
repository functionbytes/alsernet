<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentFlowNode extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_flow_nodes';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'data' => 'array',
        'position' => 'array',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'flow_id',
        'node_id',
        'type', // 'input', 'prompt', 'condition', 'action', 'output'
        'label',
        'data', // JSON: node-specific data
        'position', // JSON: x, y coordinates
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // ==================== Accessors ====================

    /**
     * Get node type label
     */
    public function getTypeLabelAttribute()
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
}
