<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiAgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('helpdesk.ai-agents.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'system_prompt' => ['required', 'string', 'max:10000'],
            'max_messages_before_escalation' => ['required', 'integer', 'min:1', 'max:100'],
            'enabled_channels' => ['nullable', 'array'],
            'enabled_channels.*' => ['in:web,facebook,instagram,whatsapp,email'],
            'escalation_keywords' => ['nullable', 'string'],
            'knowledge_sources' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'system_prompt.required' => 'El prompt del sistema es obligatorio.',
            'system_prompt.max' => 'El prompt del sistema no puede superar los 10.000 caracteres.',
            'max_messages_before_escalation.required' => 'El numero de mensajes antes de escalar es obligatorio.',
            'max_messages_before_escalation.integer' => 'Debe ser un numero entero.',
            'max_messages_before_escalation.min' => 'El minimo es 1 mensaje.',
            'max_messages_before_escalation.max' => 'El maximo es 100 mensajes.',
            'enabled_channels.*.in' => 'Canal no valido.',
            'description.max' => 'La descripcion no puede superar los 1.000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'system_prompt' => 'prompt del sistema',
            'max_messages_before_escalation' => 'mensajes antes de escalar',
            'enabled_channels' => 'canales habilitados',
            'escalation_keywords' => 'palabras clave de escalacion',
            'knowledge_sources' => 'fuentes de conocimiento',
            'is_active' => 'estado activo',
        ];
    }
}
