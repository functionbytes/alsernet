<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * Espeja las reglas de DocumentValidationController::uploadDocument()
     * (módulo Document) para validar antes de delegar.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'documents' => ['nullable', 'array'],
            // Whitelist de tipos obligatoria: el destino es el disco público de
            // media-library, así que sin `mimes` un agente podría subir .php/.html/.svg
            // a una URL servible (RCE / XSS almacenado). Misma lista que el request
            // hermano UploadDocumentAttachmentRequest.
            'documents.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.array' => 'El formato de los documentos no es válido.',
            'documents.*.file' => 'Cada documento debe ser un archivo válido.',
            'documents.*.max' => 'Cada archivo no puede superar los 10 MB.',
            'documents.*.mimes' => 'Solo se permiten archivos PDF, imágenes (jpg, png) o Word (doc, docx).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'documents' => 'documentos',
        ];
    }
}
