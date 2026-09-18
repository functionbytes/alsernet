<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Los dos únicos interruptores de un buzón que el modal "Buzones" de la
 * pantalla de tickets puede tocar. Deliberadamente NO acepta host, puerto,
 * usuario ni contraseña: esos siguen viviendo solo en la pantalla de ajustes
 * (TicketEmailChannelsController), donde el formulario completo valida el
 * guard SSRF y conserva la contraseña guardada. Así un cambio hecho desde el
 * riel de operación nunca puede dejar un buzón sin credenciales.
 */
class UpdateTicketMailboxBehaviorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Cambiar lo que hace un buzón con el correo entrante es un cambio de
        // configuración, no una acción de ticket: exige el mismo permiso que
        // la pantalla de ajustes de canales de correo.
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Ambos obligatorios: el modal manda siempre el par completo. Si uno
        // pudiera faltar, un envío parcial dejaría el otro con el valor por
        // defecto de boolean() (false) y apagaría el buzón sin quererlo.
        return [
            'create_tickets' => ['required', 'boolean'],
            'create_replies' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'create_tickets.required' => 'Falta indicar si el buzón crea tickets.',
            'create_replies.required' => 'Falta indicar si el buzón añade respuestas.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'create_tickets' => 'crear tickets con los correos entrantes',
            'create_replies' => 'añadir respuestas al ticket existente',
        ];
    }
}
