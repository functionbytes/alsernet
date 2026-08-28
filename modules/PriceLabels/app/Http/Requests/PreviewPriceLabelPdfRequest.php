<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PriceLabels\Services\PriceLabelFontService;

class PreviewPriceLabelPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricelabels.update');
    }

    public function rules(): array
    {
        return [
            'orientation' => ['required', 'string', Rule::in(['vertical', 'horizontal'])],
            'positions' => ['nullable', 'array'],
            'positions.*' => ['array'],
            'positions.*.*.x' => ['required', 'integer', 'min:0'],
            'positions.*.*.y' => ['required', 'integer', 'min:0'],
            'fields' => ['nullable', 'array'],
            'fields.*.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fields.*.font_family' => ['nullable', 'string', Rule::in(app(PriceLabelFontService::class)->allowedFamilies())],
            'fields.*.font_size' => ['nullable', 'integer', 'min:6', 'max:72'],
            'fields.*.bold' => ['boolean'],
            'fields.*.italic' => ['boolean'],
            'fields.*.font_family_h' => ['nullable', 'string', Rule::in(app(PriceLabelFontService::class)->allowedFamilies())],
            'fields.*.font_size_h' => ['nullable', 'integer', 'min:6', 'max:72'],
            'fields.*.align' => ['nullable', 'string', 'in:left,center,right'],
            'fields.*.box_w' => ['nullable', 'integer', 'min:10', 'max:2000'],
            'fields.*.box_h' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'orientation.required' => 'La orientacion es obligatoria.',
            'orientation.in' => 'La orientacion debe ser vertical u horizontal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'orientation' => 'orientacion',
            'positions' => 'posiciones',
            'fields' => 'estilos de campo',
        ];
    }
}
