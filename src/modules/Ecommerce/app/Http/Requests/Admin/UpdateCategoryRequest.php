<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('ecommerce_product_categories', 'slug')->ignore($categoryId)],
            'parent_id' => ['nullable', 'exists:ecommerce_product_categories,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:published,draft,pending'],
            'order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'slug' => 'slug',
            'parent_id' => 'categoria padre',
            'description' => 'descripcion',
            'status' => 'estado',
            'order' => 'orden',
            'image' => 'imagen',
            'is_featured' => 'destacado',
        ];
    }
}
