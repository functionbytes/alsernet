<?php

namespace Modules\PriceLabels\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GeneratePriceLabelPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pricelabels.view');
    }

    public function rules(): array
    {
        return [
            'excel_file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls'],
            'type' => ['required', 'string', Rule::in(['vertical', 'horizontal'])],
        ];
    }

    public function messages(): array
    {
        return [
            'excel_file.required' => 'Debes subir un archivo Excel (XLSX o XLS).',
            'excel_file.mimes' => 'El archivo debe ser XLSX o XLS.',
            'type.in' => 'El tipo debe ser vertical u horizontal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'excel_file' => 'archivo Excel',
            'type' => 'tipo',
        ];
    }
}
