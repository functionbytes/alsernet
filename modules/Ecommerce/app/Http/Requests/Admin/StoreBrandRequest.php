<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url'],
            'logo' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,pending'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripcion',
            'website' => 'sitio web',
            'logo' => 'logo',
            'status' => 'estado',
            'order' => 'orden',
            'is_featured' => 'destacado',
        ];
    }
}
