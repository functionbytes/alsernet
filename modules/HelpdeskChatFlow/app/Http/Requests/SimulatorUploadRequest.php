<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimulatorUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chatflow.update');
    }

    public function rules(): array
    {
        return [
            'session_key' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9_-]+$/'],
            'doc_key' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9_-]+$/'],
            // 29-sep-2026: lista blanca por contenido (mimes) y por nombre (extensions).
            'file' => ['required', 'file', 'max:20480',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
                'extensions:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_key.required' => 'Falta la sesión de prueba.',
            'session_key.regex' => 'La sesión de prueba no es válida.',
            'doc_key.required' => 'Falta el documento a subir.',
            'doc_key.regex' => 'El identificador del documento no es válido.',
            'file.required' => 'Adjunta un archivo.',
            'file.max' => 'El archivo no puede superar los 20 MB.',
            'file.mimes' => 'Tipo de archivo no permitido (imágenes, PDF, Office o texto).',
            'file.extensions' => 'Tipo de archivo no permitido (imágenes, PDF, Office o texto).',
        ];
    }

    public function attributes(): array
    {
        return [
            'session_key' => 'sesión',
            'doc_key' => 'documento',
            'file' => 'archivo',
        ];
    }
}
