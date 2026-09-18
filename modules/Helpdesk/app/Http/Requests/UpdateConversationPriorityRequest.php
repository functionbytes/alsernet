<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationPriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'priority' => ['required', 'in:low,normal,high,urgent'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.in' => 'La prioridad debe ser baja, normal, alta o urgente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'priority' => 'prioridad',
        ];
    }
}
