<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFormCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.categories.manage');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:form_categories,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Falta el listado de categorías a reordenar.',
            'items.*.id.required' => 'Cada item debe incluir un ID.',
            'items.*.sort_order.min' => 'El orden no puede ser negativo.',
        ];
    }
}
