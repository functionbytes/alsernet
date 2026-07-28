<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceLabelFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricelabels.update');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'excel_column' => ['required', 'string', 'regex:/^[A-Za-z]{1,2}$/'],
            'type' => ['required', 'string', Rule::in(['text', 'price'])],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del campo es obligatorio.',
            'excel_column.required' => 'La columna del Excel es obligatoria.',
            'excel_column.regex' => 'La columna debe ser una letra valida (ej: A, B, AA).',
            'type.in' => 'El tipo debe ser texto o precio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'nombre del campo',
            'excel_column' => 'columna del Excel',
            'type' => 'tipo',
        ];
    }
}
