<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.categories.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'color.regex' => 'El color debe estar en formato hexadecimal (ej. #90bb13).',
            'icon.max' => 'El icono no puede superar los 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'color' => 'color',
            'icon' => 'icono',
            'is_active' => 'estado',
        ];
    }
}
