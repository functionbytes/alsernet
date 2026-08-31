<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StorePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:helpdesk.helpdesk_priorities,slug', 'regex:/^[a-z0-9_-]+$/'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'response_time_hours' => ['required', 'integer', 'min:1'],
            'resolution_time_hours' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe ser un codigo hexadecimal valido (#RRGGBB).',
            'level.required' => 'El nivel de prioridad es obligatorio.',
            'level.integer' => 'El nivel de prioridad debe ser un numero entero.',
            'level.min' => 'El nivel de prioridad minimo es 1.',
            'level.max' => 'El nivel de prioridad maximo es 100.',
            'response_time_hours.required' => 'El tiempo de primera respuesta es obligatorio.',
            'resolution_time_hours.required' => 'El tiempo de resolucion es obligatorio.',
            'slug.unique' => 'El slug ya esta en uso.',
            'slug.regex' => 'El slug solo puede contener letras minusculas, numeros, guiones y guiones bajos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'color' => 'color',
            'level' => 'nivel de prioridad',
            'response_time_hours' => 'tiempo de primera respuesta',
            'resolution_time_hours' => 'tiempo de resolucion',
            'is_active' => 'activa',
        ];
    }
}
