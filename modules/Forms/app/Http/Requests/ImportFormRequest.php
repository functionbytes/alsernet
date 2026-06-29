<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.create');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:application/json,text/plain', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo adjunto no es válido.',
            'file.mimetypes' => 'El archivo debe ser un JSON válido.',
            'file.max' => 'El archivo no puede superar los 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
        ];
    }
}
