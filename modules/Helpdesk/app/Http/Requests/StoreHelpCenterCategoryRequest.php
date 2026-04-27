<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpCenterCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'visible_to_role' => ['nullable', 'string', 'max:255'],
            'managed_by_role' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'icon.max' => 'El icono no puede superar los 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'image' => 'imagen',
            'icon' => 'icono',
            'visible_to_role' => 'rol visible para',
            'managed_by_role' => 'rol gestor',
        ];
    }
}
