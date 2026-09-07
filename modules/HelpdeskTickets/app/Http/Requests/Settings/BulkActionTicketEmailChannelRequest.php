<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Acción masiva sobre canales de correo.
 *
 * No extiende BulkActionRequest como el resto de catálogos de Ajustes porque
 * los canales no son filas de una tabla: viven dentro del blob `incoming_email`
 * y su id es un string (uniqid), no un entero autoincremental. La regla
 * `ids.* => integer` de la base los rechazaría todos.
 */
class BulkActionTicketEmailChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos un canal.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos un canal.',
            'ids.*.string' => 'Cada identificador de canal debe ser una cadena.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'ids' => 'identificadores',
            'ids.*' => 'identificador',
        ];
    }
}
