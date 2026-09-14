<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'order_reference' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type_id.exists' => 'El tipo de documento seleccionado no existe.',
            'order_reference.max' => 'La referencia no puede superar los 64 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type_id' => 'tipo de documento',
            'order_reference' => 'referencia',
        ];
    }
}
