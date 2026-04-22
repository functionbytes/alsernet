<?php

namespace Modules\HelpdeskAgents\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentFlowNode extends Model
{
    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_ai_agent_flow_nodes';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'position' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'flow_id',
        'node_id',
        'type',
        'label',
        'data',
        'position',
    ];

    // ==================== Relationships ====================

    public function flow(): BelongsTo
    {
        return $this->belongsTo(AiAgentFlow::class, 'flow_id');
    }

    // ==================== Scopes ====================

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== Accessors ====================

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                'input' => 'Entrada',
                'prompt' => 'Prompt',
                'condition' => 'Condición',
                'action' => 'Acción',
                'output' => 'Salida',
                default => $this->type,
            },
        );
    }
}
