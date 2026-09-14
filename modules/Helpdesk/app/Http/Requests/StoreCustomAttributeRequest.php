<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.attributes.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('helpdesk.helpdesk_attributes', 'name')->where(fn ($query) => $query->where('active', true)),
            ],
            'key' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('helpdesk.helpdesk_attributes', 'key'),
            ],
            'model_type' => ['required', 'in:conversation,contact'],
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
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 60 caracteres.',
            'name.unique' => 'Ya existe un atributo activo con este nombre.',
            'key.required' => 'La clave es obligatoria.',
            'key.max' => 'La clave no puede superar los 60 caracteres.',
            'key.regex' => 'La clave solo puede contener letras minúsculas, números y guiones bajos.',
            'key.unique' => 'Ya existe un atributo con esta clave.',
            'model_type.required' => 'El tipo de modelo es obligatorio.',
            'model_type.in' => 'El tipo de modelo debe ser conversación o contacto.',
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
            'key' => 'clave',
            'format' => 'formato',
            'required' => 'obligatorio',
            'permission' => 'permiso',
            'description' => 'descripción',
            'options' => 'opciones',
            'model_type' => 'tipo de modelo',
            'min_value' => 'valor mínimo',
            'max_value' => 'valor máximo',
            'active' => 'activo',
        ];
    }
}
