<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class ActionContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:approve,reject,regenerate,publish_erp'],
            'notes' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'publicar' => ['nullable', 'integer', 'in:0,1'],
            'nombre' => ['nullable', 'string', 'max:500'],
            'marca' => ['nullable', 'string', 'max:200'],
            'full_update' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La accion es obligatoria.',
            'action.in' => 'La accion seleccionada no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'accion',
            'notes' => 'notas',
            'reason' => 'motivo',
        ];
    }
}
