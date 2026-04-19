<?php

namespace Modules\Modules\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('modules.manage');
    }

    public function rules(): array
    {
        return [
            'module_file' => ['required', 'file', 'mimes:zip', 'max:51200'],
        ];
    }

    public function messages(): array
    {
        return [
            'module_file.required' => 'Debes seleccionar un archivo de módulo.',
            'module_file.file' => 'El archivo de módulo no es válido.',
            'module_file.mimes' => 'El archivo del módulo debe ser un archivo ZIP.',
            'module_file.max' => 'El archivo del módulo no puede superar los 50 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'module_file' => 'archivo del módulo',
        ];
    }
}
