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

    /**
     * Acepta tanto `types[]` (varios formatos a la vez) como el `type` simple.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('types') && $this->filled('type')) {
            $this->merge(['types' => [$this->input('type')]]);
        }
    }

    public function rules(): array
    {
        return [
            'excel_file' => ['required', 'file', 'max:10240', 'mimes:xlsx,xls'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['string', Rule::in(['vertical', 'horizontal'])],
        ];
    }

    public function messages(): array
    {
        return [
            'excel_file.required' => 'Debes subir un archivo Excel (XLSX o XLS).',
            'excel_file.mimes' => 'El archivo debe ser XLSX o XLS.',
            'types.required' => 'Debes seleccionar al menos un formato.',
            'types.min' => 'Debes seleccionar al menos un formato.',
            'types.*.in' => 'El formato debe ser vertical u horizontal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'excel_file' => 'archivo Excel',
            'types' => 'formatos',
        ];
    }
}
