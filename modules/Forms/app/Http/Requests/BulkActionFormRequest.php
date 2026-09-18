<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit')
            || $this->user()->can('Forms.forms.delete');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'exists:forms,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'La acción es obligatoria.',
            'action.in' => 'La acción seleccionada no es válida.',
            'ids.required' => 'Debes seleccionar al menos un formulario.',
            'ids.array' => 'El listado de formularios no es válido.',
            'ids.min' => 'Debes seleccionar al menos un formulario.',
            'ids.max' => 'No puedes operar sobre más de 500 formularios a la vez.',
            'ids.*.integer' => 'Hay identificadores de formulario inválidos en la selección.',
            'ids.*.exists' => 'Uno o más formularios seleccionados ya no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'formularios seleccionados',
        ];
    }
}
