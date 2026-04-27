<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderConversationViewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:helpdesk_conversation_views,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'La lista de identificadores es obligatoria.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.*.exists' => 'Una o más vistas seleccionadas no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identificadores',
        ];
    }
}
