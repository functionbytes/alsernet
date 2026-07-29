<?php

namespace Modules\Supplier\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.products') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'available' => 'boolean',
            'web_published' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del producto es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'available.boolean' => 'El campo disponible debe ser verdadero o falso.',
            'web_published.boolean' => 'El campo publicado en web debe ser verdadero o falso.',
        ];
    }
}
