<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PriceLabels\Services\PriceLabelBarcodeService;
use Modules\PriceLabels\Services\PriceLabelTemplateService;

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
            'excel_column' => [
                'required', 'string', 'regex:/^[A-Za-z]{1,2}$/',
                function ($attribute, $value, $fail) {
                    $template = $this->route('price_label_template');
                    $definitions = $template?->field_definitions ?: app(PriceLabelTemplateService::class)->defaultFieldDefinitions();
                    $usedColumns = collect($definitions)
                        ->pluck('excel_column')
                        ->map(fn ($column) => strtoupper($column));

                    if ($usedColumns->contains(strtoupper($value))) {
                        $fail('Esa columna del Excel ya esta usada por otro campo.');
                    }
                },
            ],
            'type' => ['required', 'string', Rule::in(['text', 'price', 'barcode', 'qr'])],
            'barcode_type' => [
                'nullable',
                'required_if:type,barcode',
                'string',
                Rule::in(array_keys(PriceLabelBarcodeService::SYMBOLOGIES)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del campo es obligatorio.',
            'excel_column.required' => 'La columna del Excel es obligatoria.',
            'excel_column.regex' => 'La columna debe ser una letra valida (ej: A, B, AA).',
            'type.in' => 'El tipo debe ser texto, precio, codigo de barras o QR.',
            'barcode_type.required_if' => 'Elige la simbologia del codigo de barras.',
            'barcode_type.in' => 'Esa simbologia de codigo de barras no es valida.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'nombre del campo',
            'excel_column' => 'columna del Excel',
            'type' => 'tipo',
            'barcode_type' => 'simbologia',
        ];
    }
}
