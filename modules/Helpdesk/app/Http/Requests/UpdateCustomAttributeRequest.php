<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Helpdesk\Models\CustomAttribute;

class UpdateCustomAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        $attribute = $this->route('id')
            ? CustomAttribute::withoutGlobalScope('active')->find($this->route('id'))
            : null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('helpdesk.helpdesk_attributes', 'name')
                    ->ignore($attribute?->id)
                    ->where(fn ($query) => $query->where('active', true)),
            ],
            'format' => ['required', 'string', 'max:50'],
            'required' => ['nullable', 'boolean'],
            'permission' => ['required', 'in:userCanView,userCanEdit,agentCanEdit'],
            'description' => ['nullable', 'string', 'max:600'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'active' => ['nullable', 'boolean'],
        ];

        if ($attribute?->internal) {
            unset($rules['required'], $rules['permission']);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 60 caracteres.',
            'name.unique' => 'Ya existe un atributo activo con este nombre.',
            'format.required' => 'El formato es obligatorio.',
            'permission.required' => 'El permiso es obligatorio.',
            'permission.in' => 'El permiso debe ser: puede ver, puede editar (usuario) o puede editar (agente).',
            'description.max' => 'La descripción no puede superar los 600 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'format' => 'formato',
            'required' => 'obligatorio',
            'permission' => 'permiso',
            'description' => 'descripción',
            'options' => 'opciones',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'active' => 'activo',
        ];
    }
}
