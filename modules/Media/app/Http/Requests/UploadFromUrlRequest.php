<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFromUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
            'filename' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'La URL es obligatoria.',
            'url.url' => 'La URL no tiene un formato válido.',
            'url.max' => 'La URL no puede superar los 2048 caracteres.',
            'filename.max' => 'El nombre de archivo no puede superar los 255 caracteres.',
            'folder_id.exists' => 'La carpeta seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'url' => 'URL',
            'filename' => 'nombre de archivo',
            'folder_id' => 'carpeta',
        ];
    }
}
