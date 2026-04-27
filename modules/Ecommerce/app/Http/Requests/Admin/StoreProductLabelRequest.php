<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.product-labels.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status' => ['required', 'string', 'in:published,draft'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 120 caracteres.',
            'color.regex' => 'El color de fondo debe ser un color hexadecimal válido (ej: #FF0000).',
            'text_color.regex' => 'El color de texto debe ser un color hexadecimal válido (ej: #FFFFFF).',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser publicado o borrador.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'color' => 'color de fondo',
            'text_color' => 'color de texto',
            'status' => 'estado',
        ];
    }
}
