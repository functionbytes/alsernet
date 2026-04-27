<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.automations.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_event' => ['required', 'string', 'max:100'],
            'conditions' => ['nullable', 'json'],
            'actions' => ['required', 'json'],
            'active' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la automatización es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'trigger_event.required' => 'El evento de disparo es obligatorio.',
            'trigger_event.max' => 'El evento no puede superar los 100 caracteres.',
            'actions.required' => 'Las acciones son obligatorias.',
            'actions.json' => 'Las acciones deben ser un JSON válido.',
            'conditions.json' => 'Las condiciones deben ser un JSON válido.',
            'priority.min' => 'La prioridad no puede ser menor a 0.',
            'priority.max' => 'La prioridad no puede superar 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'trigger_event' => 'evento de disparo',
            'conditions' => 'condiciones',
            'actions' => 'acciones',
            'active' => 'activo',
            'priority' => 'prioridad',
        ];
    }
}
