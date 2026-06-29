<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSearchConsoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.index') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Debe subir un archivo CSV de Search Console.',
            'csv_file.mimes' => 'El archivo debe ser CSV o TXT.',
            'csv_file.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }
}
