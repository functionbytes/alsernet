<?php

namespace Modules\Cookie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCookieInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cookie.inventory.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:cookie_inventory,name'],
            'provider' => ['required', 'string', 'max:100'],
            'category' => ['required', 'in:essential,analytics,marketing,preferences'],
            'purpose' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'in:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe una cookie con ese nombre.',
            'provider.required' => 'El proveedor es obligatorio.',
            'category.required' => 'La categoría es obligatoria.',
            'category.in' => 'La categoría seleccionada no es válida.',
            'purpose.required' => 'El propósito es obligatorio.',
            'duration.required' => 'La duración es obligatoria.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'provider' => 'proveedor',
            'category' => 'categoría',
            'purpose' => 'propósito',
            'duration' => 'duración',
            'is_active' => 'estado',
            'sort_order' => 'orden',
        ];
    }
}
