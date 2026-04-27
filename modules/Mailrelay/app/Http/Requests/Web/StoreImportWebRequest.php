<?php

namespace Modules\Mailrelay\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportWebRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.imports.create');
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'list_id' => ['nullable', 'exists:mails_lists,id'],
            'validate_emails' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El campo debe ser un archivo.',
            'file.mimes' => 'El archivo debe ser CSV, XLSX o XLS.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            'list_id.exists' => 'La lista seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'list_id' => 'lista',
            'validate_emails' => 'validar correos',
        ];
    }
}
