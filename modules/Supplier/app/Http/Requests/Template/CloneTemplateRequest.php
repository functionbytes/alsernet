<?php

namespace Modules\Supplier\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;

class CloneTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.templates.manage');
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'category_id' => ['nullable', 'integer', 'exists:supplier_categories,id'],
            'scope' => ['required', 'string', 'in:global,supplier,category,supplier_category,source'],
            'label' => ['required', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'scope.required' => 'El ambito es obligatorio.',
            'scope.in' => 'El ambito seleccionado no es valido.',
            'label.required' => 'El nombre del prompt es obligatorio.',
            'label.max' => 'El nombre no puede superar los 255 caracteres.',
            'priority.min' => 'La prioridad minima es 0.',
            'priority.max' => 'La prioridad maxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'proveedor',
            'category_id' => 'categoria',
            'scope' => 'ambito',
            'label' => 'nombre',
            'priority' => 'prioridad',
        ];
    }
}
