<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:helpdesk.helpdesk_ticket_categories,slug', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'default_sla_policy_id' => ['nullable', 'exists:helpdesk.helpdesk_ticket_sla_policies,id'],
            'required_fields' => ['nullable', 'array'],
            'active' => ['nullable', 'boolean'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['exists:helpdesk.helpdesk_ticket_groups,id'],
            'default_group' => ['nullable', 'exists:helpdesk.helpdesk_ticket_groups,id'],
            'canned_replies' => ['nullable', 'array'],
            'canned_replies.*' => ['exists:helpdesk.helpdesk_ticket_canned_replies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'slug.unique' => 'El slug ya esta en uso.',
            'slug.regex' => 'El slug solo puede contener letras minusculas, numeros, guiones y guiones bajos.',
            'color.regex' => 'El color debe ser un codigo hexadecimal valido (#RRGGBB).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'description' => 'descripcion',
            'icon' => 'icono',
            'color' => 'color',
            'default_sla_policy_id' => 'politica SLA predeterminada',
            'active' => 'estado',
        ];
    }
}
