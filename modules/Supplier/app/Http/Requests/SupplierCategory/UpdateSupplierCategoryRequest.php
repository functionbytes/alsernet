<?php

namespace Modules\Supplier\Http\Requests\SupplierCategory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.categories.manage');
    }

    public function rules(): array
    {
        return [
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'priority.integer' => 'La prioridad debe ser un numero entero.',
            'priority.min' => 'La prioridad minima es 0.',
            'priority.max' => 'La prioridad maxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'priority' => 'prioridad',
        ];
    }
}
