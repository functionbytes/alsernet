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
            // Mismos tipos que el <input accept> del editor; se valida por contenido real (mimes) y por nombre (extensions).
            'file' => ['required', 'file', 'max:20480',
                'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx',
                'extensions:pdf,jpg,jpeg,png,gif,webp,doc,docx'],
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
            'file.mimes' => 'Formato no admitido. Usa PDF, imagen (JPG, PNG, GIF, WEBP) o Word (DOC, DOCX).',
            'file.extensions' => 'Formato no admitido. Usa PDF, imagen (JPG, PNG, GIF, WEBP) o Word (DOC, DOCX).',
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
