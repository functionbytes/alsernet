<?php

namespace Modules\Supplier\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

class EditContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.content.manage');
    }

    /**
     * Per-field length caps aligned with the underlying column constraints.
     * Without these, a long edit silently truncates or errors at the DB layer
     * with "Data too long" depending on sql_mode.
     */
    private const FIELD_LIMITS = [
        'generated_name'    => 255,
        'short_description' => 1000,
        'long_description'  => 65535,
        'seo_title'         => 70,
        'seo_description'   => 160,
        'seo_keywords'      => 255,
        'brand'             => 255,
    ];

    public function rules(): array
    {
        $field = $this->input('field');
        $maxLength = self::FIELD_LIMITS[$field] ?? 1000;

        return [
            'field' => ['required', 'string', 'in:'.implode(',', array_keys(self::FIELD_LIMITS))],
            'value' => ['nullable', 'string', 'max:'.$maxLength],
        ];
    }

    public function messages(): array
    {
        return [
            'field.required' => 'El campo a editar es obligatorio.',
            'field.in' => 'El campo seleccionado no es valido.',
            'value.required' => 'El valor es obligatorio.',
            'value.max' => 'El valor excede el limite del campo seleccionado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'field' => 'campo',
            'value' => 'valor',
        ];
    }
}
