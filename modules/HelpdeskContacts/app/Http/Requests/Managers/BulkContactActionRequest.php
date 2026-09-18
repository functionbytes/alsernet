<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkContactActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:ban,unban,delete'],
            'ids' => ['required', 'array', 'min:1'],
            // customers live on the 'helpdesk' connection (see ExecuteMergeRequest).
            'ids.*' => ['integer', 'exists:helpdesk.helpdesk_customers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción debe ser ban, unban o delete.',
            'ids.required' => 'Debes seleccionar al menos un contacto.',
            'ids.min' => 'Debes seleccionar al menos un contacto.',
            'ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'contactos',
        ];
    }
}
