<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ImportDeviceDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Alineado con el middleware de la ruta: importar archivos MUTA el
        // expediente, así que exige el permiso de gestión (antes chequeaba
        // solo conversations.view, más débil que la propia ruta).
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1'],
            // SVG excluido a propósito: puede llevar <script>/on* embebido y se
            // sirve desde el disco público (mismo origen que el panel) → stored XSS.
            // Mismo criterio que UploadDocumentFilesRequest.
            'files.*' => ['required', 'file', 'max:10240', 'mimes:avif,bmp,gif,heic,heif,jpg,jpeg,png,tif,tiff,webp'],
            'category' => ['nullable', 'string', 'max:120'],
            // Categoría por archivo, en el mismo orden que `files` (fallback a
            // `category` cuando un índice no tiene entrada propia) — permite
            // que una subida con varios archivos rellene varios tipos de
            // documento requeridos distintos en una sola acción.
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'max:120'],
            'document_id' => ['nullable', 'integer', 'exists:documents,id'],
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
            'files.*.mimes' => 'Solo se permiten archivos de imagen.',
            'category.max' => 'La categoría no puede superar los 120 caracteres.',
            'categories.*.max' => 'La categoría no puede superar los 120 caracteres.',
            'document_id.exists' => 'El expediente seleccionado no existe.',
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
            'categories' => 'categorías por archivo',
            'document_id' => 'expediente',
        ];
    }
}
