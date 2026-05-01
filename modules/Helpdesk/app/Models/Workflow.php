<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    use HasFactory;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_workflows';

    protected $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_config',
        'nodes',
        'is_active',
        'last_run_at',
        'total_runs',
    ];

    protected function casts(): array
    {
        return [
            'trigger_config' => 'array',
            'nodes' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public const TRIGGER_TYPES = [
        'conversation_created' => 'Conversacion creada',
        'message_received' => 'Mensaje recibido',
        'conversation_closed' => 'Conversacion cerrada',
        'csat_answered' => 'CSAT respondido',
        'tag_added' => 'Etiqueta agregada',
        'sla_breach' => 'SLA incumplido',
        'manual' => 'Manual',
    ];

    public const NODE_TYPES = [
        'condition' => 'Condicion',
        'action' => 'Accion',
        'wait' => 'Esperar',
        'branch' => 'Rama',
        'end' => 'Fin',
    ];

    public const ACTION_TYPES = [
        'send_text' => 'Enviar mensaje',
        'set_status' => 'Cambiar estado',
        'set_priority' => 'Cambiar prioridad',
        'assign_user' => 'Asignar agente',
        'add_tag' => 'Agregar etiqueta',
        'send_email' => 'Enviar email',
        'http_request' => 'Peticion HTTP',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class, 'workflow_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForTrigger(Builder $query, string $trigger): Builder
    {
        return $query->where('trigger_type', $trigger);
    }

    public function incrementRun(): void
    {
        $this->increment('total_runs');
        $this->update(['last_run_at' => now()]);
    }

    public function getNodeById(string $nodeId): ?array
    {
        return collect($this->nodes ?? [])->firstWhere('id', $nodeId);
    }
}
