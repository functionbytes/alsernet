<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ImportDeviceDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,gif'],
            'category' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Selecciona al menos un archivo.',
            'files.array' => 'El formato de los archivos no es válido.',
            'files.min' => 'Selecciona al menos un archivo.',
            'files.*.required' => 'Cada archivo es obligatorio.',
            'files.*.file' => 'Cada elemento debe ser un archivo válido.',
            'files.*.max' => 'Cada archivo no puede superar los 10 MB.',
            'files.*.mimes' => 'Solo se permiten archivos PDF o imágenes (jpg, png, webp, gif).',
            'category.max' => 'La categoría no puede superar los 120 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'files' => 'archivos',
            'category' => 'categoría',
        ];
    }
}
