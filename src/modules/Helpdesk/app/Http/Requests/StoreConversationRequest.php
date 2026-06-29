<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:helpdesk.helpdesk_customers,id'],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'status_id' => ['required', 'exists:helpdesk.helpdesk_conversation_statuses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'El cliente es obligatorio.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'subject.required' => 'El asunto es obligatorio.',
            'subject.max' => 'El asunto no puede superar los 255 caracteres.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.in' => 'La prioridad debe ser: baja, normal, alta o urgente.',
            'status_id.required' => 'El estado es obligatorio.',
            'status_id.exists' => 'El estado seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'subject' => 'asunto',
            'priority' => 'prioridad',
            'status_id' => 'estado',
        ];
    }
}
