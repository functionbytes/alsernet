<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePriceLabelPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricelabels.update');
    }

    public function rules(): array
    {
        return [
            'orientation' => ['required', 'string', Rule::in(['vertical', 'horizontal'])],
            'positions' => ['required', 'array'],
            'positions.*' => ['array'],
            'positions.*.*.x' => ['required', 'integer', 'min:0'],
            'positions.*.*.y' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'orientation.required' => 'La orientacion es obligatoria.',
            'orientation.in' => 'La orientacion debe ser vertical u horizontal.',
            'positions.required' => 'Las posiciones son obligatorias.',
        ];
    }

    public function attributes(): array
    {
        return [
            'orientation' => 'orientacion',
            'positions' => 'posiciones',
        ];
    }
}
