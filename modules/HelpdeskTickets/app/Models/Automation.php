<?php

namespace Modules\HelpdeskTickets\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Automation extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'helpdesk';

    protected $table = 'helpdesk_automations';

    protected $fillable = [
        'name',
        'description',
        'trigger_event',
        'conditions',
        'actions',
        'order',
        'is_active',
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
        ];
    }

    public static array $triggerEvents = [
        'ticket.created' => 'Ticket creado',
        'ticket.updated' => 'Ticket actualizado',
        'ticket.assigned' => 'Ticket asignado',
        'ticket.resolved' => 'Ticket resuelto',
        'ticket.closed' => 'Ticket cerrado',
        // Se emite cuando la búsqueda del cliente en el ERP termina, encontrado
        // o no. Es el único disparador desde el que las condiciones erp_* ven
        // datos: en ticket.created el trabajo sigue en la cola helpdesk-erp.
        'ticket.erp_resolved' => 'El ERP ha respondido',
    ];

    /**
     * `helpdesk_automations` es la MISMA tabla física que usa
     * Modules\Helpdesk\Models\AutomationRule (el motor de Conversaciones,
     * disparadores conversation.* / message.*) — dos Eloquent models de dos
     * módulos distintos apuntando al mismo sitio, cada uno con su propio
     * motor (Modules\HelpdeskTickets\Services\AutomationEngine aquí,
     * Modules\Helpdesk\Services\Automation\AutomationEngine allí).
     *
     * Sin este scope, cualquier lectura/edición desde el lado de Tickets
     * (modal "Reglas de escalado" y Ajustes → Automatizaciones) traía
     * también las reglas de Conversaciones: el modal las marcaba como
     * "disparador que el motor no conoce — no se ejecutan nunca", que es
     * falso (sí las ejecuta el otro motor, con run_count real), y Ajustes
     * dejaba editarlas/borrarlas con un formulario que ni siquiera ofrece
     * sus disparadores reales como opción. Ver 8-sep-2026.
     */
    public function scopeTicketDomain(Builder $query): Builder
    {
        return $query->whereIn('trigger_event', array_keys(self::$triggerEvents));
    }
}
