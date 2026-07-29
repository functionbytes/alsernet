<?php

namespace Modules\Supplier\Http\Requests\Source;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.sources.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:enable,disable,delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'ids.required' => 'Debe seleccionar al menos una fuente.',
            'ids.array' => 'Los identificadores deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos una fuente.',
            'ids.*.required' => 'Cada identificador es obligatorio.',
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
