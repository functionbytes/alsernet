<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketNotificationPreferencesRequest extends FormRequest
{
    /**
     * No se pide ningún permiso de helpdesk: el agente sólo edita SUS propias
     * preferencias de aviso (el controlador escribe siempre con el id del
     * usuario autenticado, nunca con uno que venga en la petición). Basta con
     * estar dentro, que ya lo garantiza el middleware del grupo de rutas.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array', 'min:1', 'max:50'],
            'preferences.*.notification_type' => ['required', 'string', 'max:100'],
            // Los dos únicos canales que alguna via() de este módulo consulta.
            // 'email' existe en la tabla pero ninguna notificación de tickets
            // lo mira, así que aceptarlo sólo crearía filas muertas.
            'preferences.*.channel' => ['required', 'string', 'in:in_app,push'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'No se ha recibido ninguna preferencia que guardar',
            'preferences.*.notification_type.required' => 'Falta el evento al que se refiere la preferencia',
            'preferences.*.channel.in' => 'El canal debe ser el panel o el aviso en tiempo real',
            'preferences.*.enabled.required' => 'Falta indicar si el aviso queda activado o desactivado',
        ];
    }
}
