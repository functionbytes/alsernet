<?php

namespace Modules\Supplier\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.templates.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete'],
            'uids' => ['required', 'array', 'min:1'],
            'uids.*' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'uids.required' => 'Debe seleccionar al menos una plantilla.',
            'uids.array' => 'Los identificadores deben ser un arreglo.',
            'uids.min' => 'Debe seleccionar al menos una plantilla.',
            'uids.*.required' => 'Cada identificador es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'uids' => 'identificadores',
            'uids.*' => 'identificador',
        ];
    }
}
