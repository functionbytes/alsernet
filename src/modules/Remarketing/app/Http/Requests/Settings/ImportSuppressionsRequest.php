<?php

namespace Modules\Remarketing\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class ImportSuppressionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.suppressions.manage');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:remarketing_stores,id'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'reason' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecciona un archivo CSV.',
            'file.mimes' => 'El archivo debe ser CSV o TXT.',
            'file.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'store_id' => 'tienda',
            'file' => 'archivo',
            'reason' => 'motivo',
        ];
    }
}
