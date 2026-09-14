<?php

namespace Modules\Supplier\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.configure');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,enable,disable'],
            'uids' => ['required', 'array', 'min:1'],
            'uids.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'uids.required' => 'Debe seleccionar al menos un proveedor.',
            'uids.array' => 'Los identificadores deben ser un arreglo.',
            'uids.min' => 'Debe seleccionar al menos un proveedor.',
            'uids.*.required' => 'Cada identificador es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'uids' => 'identificadores',
            'uids.*' => 'identificador',
        ];
    }
}
