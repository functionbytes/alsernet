<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class BulkSendHsmRequest extends FormRequest
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
            'customer_ids' => ['required', 'array', 'min:1'],
            // customers live on the 'helpdesk' connection (see ExecuteMergeRequest).
            'customer_ids.*' => ['integer', 'exists:helpdesk.helpdesk_customers,id'],
            'template_name' => ['required', 'string', 'max:255'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['nullable', 'string', 'max:1000'],
            'language' => ['nullable', 'string', 'in:es,en,pt'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_ids.required' => 'Debes seleccionar al menos un contacto.',
            'customer_ids.min' => 'Debes seleccionar al menos un contacto.',
            'customer_ids.*.exists' => 'Uno o más contactos seleccionados no existen.',
            'template_name.required' => 'El nombre de la plantilla es obligatorio.',
            'variables.array' => 'Las variables deben ser un listado.',
            'language.in' => 'El idioma debe ser: es, en o pt.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_ids' => 'contactos',
            'template_name' => 'plantilla',
            'variables' => 'variables',
            'language' => 'idioma',
        ];
    }
}
