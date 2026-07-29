<?php

namespace Modules\Supplier\Http\Requests\Prompt;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.prompts.manage');
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
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
            'uids.required' => 'Debe seleccionar al menos un prompt.',
            'uids.array' => 'Los identificadores deben ser un arreglo.',
            'uids.min' => 'Debe seleccionar al menos un prompt.',
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
