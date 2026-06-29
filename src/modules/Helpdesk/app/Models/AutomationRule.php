<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutomationRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    // Real table name in DB
    protected $table = 'helpdesk_automations';

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'conditions',
        'actions',
        'is_active',
        'order',
        'run_count',
        'last_run_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public const EVENTS = [
        'conversation.created' => 'Conversacion creada',
        'conversation.updated' => 'Conversacion actualizada',
        'conversation.assigned' => 'Conversacion asignada',
        'conversation.resolved' => 'Conversacion resuelta',
        'message.created' => 'Nuevo mensaje recibido',
    ];

    public const CONDITION_FIELDS = [
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'assignee' => 'Agente asignado',
        'tag' => 'Etiqueta',
        'channel' => 'Canal',
        'subject' => 'Asunto',
    ];

    public const CONDITION_OPERATORS = [
        'equals' => 'Es igual a',
        'not_equals' => 'No es igual a',
        'contains' => 'Contiene',
        'not_contains' => 'No contiene',
        'is_empty' => 'Esta vacio',
        'is_not_empty' => 'No esta vacio',
    ];

    public const ACTION_TYPES = [
        'assign_agent' => 'Asignar agente',
        'assign_group' => 'Asignar grupo',
        'add_tag' => 'Agregar etiqueta',
        'remove_tag' => 'Quitar etiqueta',
        'change_status' => 'Cambiar estado',
        'change_priority' => 'Cambiar prioridad',
        'send_email' => 'Enviar email',
        'add_note' => 'Agregar nota',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('trigger_event', $event);
    }

    public function incrementRun(): void
    {
        $this->increment('run_count');
        $this->update(['last_run_at' => now()]);
    }
}
