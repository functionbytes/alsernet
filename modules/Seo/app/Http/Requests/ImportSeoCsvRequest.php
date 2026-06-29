<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSeoCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
            'update_existing' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Debe subir un archivo CSV.',
            'csv_file.mimes' => 'El archivo debe ser CSV o TXT.',
            'csv_file.max' => 'El archivo no puede superar los 4 MB.',
        ];
    }
}
