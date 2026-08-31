<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\PriceLabels\Services\PriceLabelFontService;

class UpdatePriceLabelTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricelabels.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'is_active' => ['boolean'],
            'orientation' => ['required', 'string', Rule::in(['vertical', 'horizontal', 'both'])],
            'label_text' => ['nullable', 'string', 'max:255'],
            'image_vertical' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_horizontal' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'vertical_rows' => ['nullable', 'integer', 'min:1', 'max:20'],
            'vertical_columns' => ['nullable', 'integer', 'min:1', 'max:20'],
            'horizontal_rows' => ['nullable', 'integer', 'min:1', 'max:20'],
            'horizontal_columns' => ['nullable', 'integer', 'min:1', 'max:20'],
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
            'fields.*.box_w_h' => ['nullable', 'integer', 'min:10', 'max:2000'],
            'fields.*.box_h_h' => ['nullable', 'integer', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'orientation.required' => 'Selecciona para que orientacion es esta plantilla.',
            'orientation.in' => 'La orientacion debe ser vertical, horizontal o ambas.',
            'image_vertical.image' => 'La imagen vertical debe ser un archivo de imagen.',
            'image_vertical.mimes' => 'La imagen vertical debe ser JPG, PNG o WEBP.',
            'image_horizontal.image' => 'La imagen horizontal debe ser un archivo de imagen.',
            'image_horizontal.mimes' => 'La imagen horizontal debe ser JPG, PNG o WEBP.',
            'fields.*.color.regex' => 'El color debe ser un codigo hexadecimal valido (#000000).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'is_active' => 'estado',
            'orientation' => 'orientacion',
            'label_text' => 'texto fijo',
            'image_vertical' => 'imagen vertical',
            'image_horizontal' => 'imagen horizontal',
            'vertical_rows' => 'filas (vertical)',
            'vertical_columns' => 'columnas (vertical)',
            'horizontal_rows' => 'filas (horizontal)',
            'horizontal_columns' => 'columnas (horizontal)',
        ];
    }
}
