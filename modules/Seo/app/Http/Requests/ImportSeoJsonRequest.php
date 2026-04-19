<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportSeoJsonRequest extends FormRequest
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
            'json_file' => ['required', 'file', 'mimes:json,txt', 'max:10240'],
            'import_redirects' => ['sometimes', 'boolean'],
            'import_templates' => ['sometimes', 'boolean'],
            'skip_existing' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'json_file.required' => 'Debe subir un archivo JSON.',
            'json_file.mimes' => 'El archivo debe ser JSON o TXT.',
            'json_file.max' => 'El archivo no puede superar los 10 MB.',
        ];
    }
}
