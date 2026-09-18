<?php

namespace Modules\Supplier\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,enable,disable'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer'],
            'type'   => ['nullable', 'string', 'in:categories,sports'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos una categoria.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos una categoria.',
            'ids.*.integer' => 'Cada identificador debe ser un numero entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'ids' => 'identificadores',
            'ids.*' => 'identificador',
        ];
    }
}
