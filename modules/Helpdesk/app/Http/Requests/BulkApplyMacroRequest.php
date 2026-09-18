<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkApplyMacroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.conversations.update');
    }

    public function rules(): array
    {
        return [
            'macro_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_macros,id'],
            'conversation_ids' => ['required', 'array', 'min:1', 'max:100'],
            'conversation_ids.*' => ['integer', 'exists:helpdesk.helpdesk_conversations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'macro_id.required' => 'Debes seleccionar un macro.',
            'macro_id.exists' => 'El macro seleccionado no existe.',
            'conversation_ids.required' => 'Debes seleccionar al menos una conversación.',
            'conversation_ids.min' => 'Debes seleccionar al menos una conversación.',
            'conversation_ids.max' => 'No puedes seleccionar más de 100 conversaciones a la vez.',
            'conversation_ids.*.exists' => 'Una o más conversaciones seleccionadas no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'macro_id' => 'macro',
            'conversation_ids' => 'conversaciones',
        ];
    }
}
