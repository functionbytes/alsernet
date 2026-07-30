<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 191 caracteres.',
            'category_id.integer' => 'La categoría seleccionada no es válida.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subject' => 'asunto',
            'category_id' => 'categoría',
            'message' => 'mensaje',
        ];
    }
}
