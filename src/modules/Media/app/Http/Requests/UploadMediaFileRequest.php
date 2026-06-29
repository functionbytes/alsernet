<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    public function rules(): array
    {
        $maxKb = (int) (config('media.max_upload_size', 104857600) / 1024);
        $mimes = config('media.allowed_mime_types', 'jpg,jpeg,png,gif,webp,svg,ico,pdf,doc,docx,xls,xlsx,zip,mp4,mp3,txt,csv');

        return [
            'file' => ['required', 'file', "max:{$maxKb}", "mimes:{$mimes}"],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo es obligatorio.',
            'file.file' => 'El campo debe ser un archivo válido.',
            'file.max' => 'El archivo supera el tamaño máximo permitido.',
            'file.mimes' => 'El tipo de archivo no está permitido.',
            'folder_id.exists' => 'La carpeta seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'folder_id' => 'carpeta',
        ];
    }
}
