<?php

namespace Modules\HelpdeskIntegration\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIntegrationProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('helpdeskintegration.providers.create');
    }

    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('helpdesk.helpdesk_integration_providers', 'platform'),
                Rule::notIn(config('helpdeskintegration.reserved_platforms', [])),
            ],
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100', 'regex:/^(fas|far|fab) fa-[a-z0-9-]+$/'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
            'is_linkable' => ['boolean'],
            'is_critical' => ['boolean'],
            'search_types' => ['nullable', 'array'],
            'search_types.*.value' => ['required_with:search_types', 'string', 'max:30'],
            'search_types.*.label' => ['required_with:search_types', 'string', 'max:50'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['nullable', 'string', 'max:1000'],
            'config' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'platform.required' => 'El identificador de plataforma es obligatorio.',
            'platform.regex' => 'El identificador solo puede contener minúsculas, números, guiones y guiones bajos.',
            'platform.unique' => 'Ya existe un proveedor con ese identificador.',
            'platform.not_in' => 'Ese identificador está reservado para un driver nativo.',
            'label.required' => 'La etiqueta es obligatoria.',
            'label.max' => 'La etiqueta no puede superar los 100 caracteres.',
            'icon.regex' => 'El icono debe ser una clase Font Awesome válida (ej. fas fa-plug).',
            'color.regex' => 'El color debe ser un hex válido (ej. #90bb13).',
        ];
    }

    public function attributes(): array
    {
        return [
            'platform' => 'identificador',
            'label' => 'etiqueta',
            'icon' => 'icono',
            'color' => 'color',
            'is_active' => 'activo',
            'is_linkable' => 'vinculable',
            'is_critical' => 'crítico',
            'sort_order' => 'orden',
            'description' => 'descripción',
        ];
    }
}
