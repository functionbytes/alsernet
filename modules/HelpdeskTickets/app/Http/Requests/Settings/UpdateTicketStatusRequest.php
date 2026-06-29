<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9_-]+$/', Rule::unique('helpdesk_ticket_statuses', 'slug')->ignore($this->route('status'))],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_open' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'stops_sla_timer' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'color.required' => 'El color es obligatorio.',
            'color.regex' => 'El color debe ser un codigo hexadecimal valido (#RRGGBB).',
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
            'description' => 'descripcion',
            'is_open' => 'estado abierto',
            'is_default' => 'estado predeterminado',
            'stops_sla_timer' => 'detiene temporizador SLA',
        ];
    }
}
