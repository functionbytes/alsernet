<?php

namespace Modules\Helpdesk\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.agents.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'is_available' => ['boolean'],
            'max_concurrent_conversations' => ['nullable', 'integer', 'min:0', 'max:100'],
            'auto_assign' => ['boolean'],
            'vacation_until' => ['nullable', 'date', 'after:now'],
            'accepts_conversations' => ['required', 'in:yes,no,working_hours'],
            // CSV de códigos ISO-639 ("es, en-GB"); se normaliza en el controller.
            'languages' => ['nullable', 'string', 'max:120', 'regex:/^[a-zA-Z]{2,3}([-_][a-zA-Z]{2,4})?(\s*,\s*[a-zA-Z]{2,3}([-_][a-zA-Z]{2,4})?)*$/'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'exists:helpdesk.helpdesk_skills,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'accepts_conversations.required' => 'El campo de aceptacion de conversaciones es obligatorio.',
            'accepts_conversations.in' => 'El valor de aceptacion de conversaciones no es valido.',
            'max_concurrent_conversations.integer' => 'El limite de conversaciones debe ser un numero entero.',
            'max_concurrent_conversations.min' => 'El limite de conversaciones no puede ser negativo.',
            'max_concurrent_conversations.max' => 'El limite de conversaciones no puede superar 100.',
            'vacation_until.date' => 'La fecha de vacaciones no tiene un formato valido.',
            'vacation_until.after' => 'La fecha de vacaciones debe ser posterior a la fecha actual.',
            'languages.regex' => 'Los idiomas deben ser codigos separados por comas (ej. "es, en").',
            'languages.max' => 'La lista de idiomas es demasiado larga.',
            'skills.*.exists' => 'Una o mas habilidades seleccionadas no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'is_available' => 'disponibilidad',
            'max_concurrent_conversations' => 'maximo de conversaciones concurrentes',
            'auto_assign' => 'asignacion automatica',
            'vacation_until' => 'vacaciones hasta',
            'accepts_conversations' => 'acepta conversaciones',
            'languages' => 'idiomas',
            'skills' => 'habilidades',
        ];
    }
}
