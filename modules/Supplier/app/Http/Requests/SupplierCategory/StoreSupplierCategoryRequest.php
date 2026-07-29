<?php

namespace Modules\Supplier\Http\Requests\SupplierCategory;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.categories.manage');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:supplier_categories,id'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'La categoria es obligatoria.',
            'category_id.exists' => 'La categoria seleccionada no existe.',
            'priority.integer' => 'La prioridad debe ser un numero entero.',
            'priority.min' => 'La prioridad minima es 0.',
            'priority.max' => 'La prioridad maxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'categoria',
            'priority' => 'prioridad',
        ];
    }
}
