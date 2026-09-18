<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:form_fields,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Falta el listado de campos a reordenar.',
            'items.*.sort_order.min' => 'El orden no puede ser negativo.',
            'items.*.id.exists' => 'Uno o más campos ya no existen.',
        ];
    }
}
