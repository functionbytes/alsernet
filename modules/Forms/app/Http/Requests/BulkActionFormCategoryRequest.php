<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionFormCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.categories.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'exists:form_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'La acción seleccionada no es válida.',
            'ids.required' => 'Debes seleccionar al menos una categoría.',
            'ids.max' => 'No puedes operar sobre más de 500 categorías a la vez.',
            'ids.*.exists' => 'Una o más categorías seleccionadas ya no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'action' => 'acción',
            'ids' => 'categorías seleccionadas',
        ];
    }
}
