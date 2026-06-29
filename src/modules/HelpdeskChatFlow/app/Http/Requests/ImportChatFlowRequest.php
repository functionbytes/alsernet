<?php

namespace Modules\HelpdeskChatFlow\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportChatFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('chatflow.create');
    }

    public function rules(): array
    {
        return [
            'file' => ['sometimes', 'file', 'max:2048', 'mimes:json,txt'],
            'json' => ['sometimes', 'string', 'max:1048576'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no puede superar los 2 MB.',
            'file.mimes' => 'El archivo debe ser un JSON exportado del editor.',
            'json.max' => 'El contenido del flow es demasiado grande.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'json' => 'contenido del flow',
        ];
    }
}
