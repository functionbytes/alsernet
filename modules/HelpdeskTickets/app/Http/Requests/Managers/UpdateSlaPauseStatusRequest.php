<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Marca (o desmarca) un estado del catálogo como "pausa el reloj de SLA".
 *
 * Es un ajuste GLOBAL del catálogo de estados, no del ticket abierto: por eso
 * exige el permiso de ajustes del módulo y no el de editar tickets. El modal
 * "Calendario y SLA" lo ofrece porque hoy NINGÚN estado lo tiene puesto y, sin
 * él, pauseSla()/resumeSla() —que sí existen y funcionan— no llegan a
 * dispararse nunca (Ticket::pauseSla() sale por el guard status->stops_sla_timer).
 */
class UpdateSlaPauseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_ticket_statuses,id'],
            'stops_sla_timer' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_id.required' => 'Falta el estado sobre el que aplicar la pausa.',
            'status_id.exists' => 'Ese estado no existe en el catálogo.',
            'stops_sla_timer.required' => 'Hay que indicar si el estado pausa el reloj o no.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status_id' => 'estado',
            'stops_sla_timer' => 'pausa del temporizador SLA',
        ];
    }
}
