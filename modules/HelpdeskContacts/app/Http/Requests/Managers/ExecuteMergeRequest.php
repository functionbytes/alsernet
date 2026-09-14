<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteMergeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.merge');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'loser_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_customers,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'loser_id.required' => 'Debes seleccionar un contacto duplicado para fusionar.',
            'loser_id.integer' => 'El identificador del contacto no es válido.',
            'loser_id.exists' => 'El contacto seleccionado no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'loser_id' => 'contacto duplicado',
        ];
    }
}
