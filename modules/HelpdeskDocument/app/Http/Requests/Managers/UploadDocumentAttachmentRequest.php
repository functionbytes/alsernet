<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El adjunto debe ser un archivo válido.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            'file.mimes' => 'Solo se permiten archivos PDF, imágenes (jpg, png) o Word (doc, docx).',
            'notes.max' => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'notes' => 'notas',
        ];
    }
}
