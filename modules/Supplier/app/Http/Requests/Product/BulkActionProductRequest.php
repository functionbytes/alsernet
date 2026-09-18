<?php

namespace Modules\Supplier\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.view.products');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,enable,disable,web_on,web_off'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos un producto.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos un producto.',
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
