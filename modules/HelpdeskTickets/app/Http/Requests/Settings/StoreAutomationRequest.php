<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskTickets\Models\Automation;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'trigger_event' => ['required', 'string', 'in:'.implode(',', array_keys(Automation::$triggerEvents))],
            'conditions' => ['required', 'json'],
            'actions' => ['required', 'json'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'trigger_event.required' => 'El evento de disparo es obligatorio.',
            'trigger_event.in' => 'El evento de disparo no es valido.',
            'conditions.required' => 'Las condiciones son obligatorias.',
            'conditions.json' => 'Las condiciones deben ser un JSON valido.',
            'actions.required' => 'Las acciones son obligatorias.',
            'actions.json' => 'Las acciones deben ser un JSON valido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'trigger_event' => 'evento de disparo',
            'conditions' => 'condiciones',
            'actions' => 'acciones',
            'order' => 'orden',
            'is_active' => 'estado',
        ];
    }
}
