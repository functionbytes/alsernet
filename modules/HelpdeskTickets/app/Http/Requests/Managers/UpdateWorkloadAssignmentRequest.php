<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Services\AutoAssignmentService;

/**
 * Ajustes del reparto automático editados desde el modal "Carga de agentes".
 *
 * Escribe la MISMA configuración global que el modal de Conversaciones
 * (helpdesk_settings, grupo `auto_assign`), así que se valida contra la lista
 * canónica de estrategias del servicio del módulo Helpdesk en vez de repetirla
 * aquí: si mañana se añade una estrategia, este request la acepta sola.
 *
 * `retry` y `fallback` NO se aceptan: solo afectan a conversaciones
 * (ReattemptAutoAssignJob / applyFallback), y dejar que la pantalla de tickets
 * los reescribiera sería cambiar a ciegas el comportamiento de otro módulo.
 */
class UpdateWorkloadAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cambiar la estrategia global afecta a todo el helpdesk (tickets y
        // conversaciones), no solo a un ticket: exige el permiso de ajustes,
        // el mismo que ya piden reintentar/purgar la cola.
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'strategy' => ['required', 'string', 'in:'.implode(',', AutoAssignmentService::STRATEGIES)],
            'language_routing' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'strategy.in' => 'La estrategia de reparto no es válida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'enabled' => 'reparto automático',
            'strategy' => 'estrategia de reparto',
            'language_routing' => 'preferencia por idioma',
        ];
    }
}
