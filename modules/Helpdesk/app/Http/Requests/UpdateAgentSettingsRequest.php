<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            'max_concurrent_conversations' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'accepts_conversations.required' => 'La disponibilidad del agente es obligatoria.',
            'accepts_conversations.in' => 'La disponibilidad debe ser: sí, no o según horario laboral.',
            'max_concurrent_conversations.integer' => 'El máximo de conversaciones debe ser un número entero.',
            'max_concurrent_conversations.min' => 'El máximo de conversaciones simultáneas debe ser al menos 1.',
            'max_concurrent_conversations.max' => 'El máximo de conversaciones simultáneas no puede superar 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'accepts_conversations' => 'disponibilidad',
            'max_concurrent_conversations' => 'máximo de conversaciones simultáneas',
        ];
    }
}
