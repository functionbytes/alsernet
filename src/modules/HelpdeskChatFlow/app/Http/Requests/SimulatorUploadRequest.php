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
            'session_key' => ['required', 'string'],
            'doc_key' => ['required', 'string'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_key.required' => 'Falta la sesión de prueba.',
            'doc_key.required' => 'Falta el documento a subir.',
            'file.required' => 'Adjunta un archivo.',
            'file.max' => 'El archivo no puede superar los 20 MB.',
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
