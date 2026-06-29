<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddConversationTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'tag_id' => ['required', 'exists:helpdesk.helpdesk_conversation_tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tag_id.required' => 'La etiqueta es obligatoria.',
            'tag_id.exists' => 'La etiqueta seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tag_id' => 'etiqueta',
        ];
    }
}
