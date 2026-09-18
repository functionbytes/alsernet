<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.status.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'severity' => ['required', 'in:minor,major,critical'],
            'status' => ['required', 'in:investigating,identified,monitoring,resolved'],
            'affected_components' => ['nullable', 'array'],
            'affected_components.*' => ['integer', 'exists:helpdesk.helpdesk_status_components,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'body.required' => 'El cuerpo del incidente es obligatorio.',
            'severity.required' => 'La severidad es obligatoria.',
            'severity.in' => 'La severidad seleccionada no es válida.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'affected_components.*.exists' => 'Uno o más componentes afectados no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'body' => 'cuerpo',
            'severity' => 'severidad',
            'status' => 'estado',
            'affected_components' => 'componentes afectados',
        ];
    }
}
