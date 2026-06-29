<?php

namespace Modules\Mailrelay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public API endpoint — authentication handled at route middleware level
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'list_id' => ['nullable', 'string', 'exists:mailrelay_groups,id'],
            'validate_emails' => ['nullable', 'boolean'],
            'sync_mailrelay' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El campo debe ser un archivo.',
            'file.mimes' => 'El archivo debe ser CSV, XLSX o XLS.',
            'file.max' => 'El archivo no puede superar los 10 MB.',
            'list_id.exists' => 'El grupo seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'list_id' => 'grupo',
            'validate_emails' => 'validar correos',
            'sync_mailrelay' => 'sincronizar con Mailrelay',
        ];
    }
}
