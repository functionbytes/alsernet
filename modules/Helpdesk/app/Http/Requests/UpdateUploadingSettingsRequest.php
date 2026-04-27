<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUploadingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'max_file_size_mb' => ['required', 'integer', 'min:1', 'max:1000'],
            'allowed_extensions' => ['required', 'string'],
            'enable_image_compression' => ['boolean'],
            'image_max_width' => ['required', 'integer', 'min:100', 'max:4000'],
            'image_max_height' => ['required', 'integer', 'min:100', 'max:4000'],
            'image_quality' => ['required', 'integer', 'min:10', 'max:100'],
            'enable_virus_scan' => ['boolean'],
            'enable_quarantine' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_file_size_mb.required' => 'El tamaño máximo de archivo es obligatorio.',
            'max_file_size_mb.min' => 'El tamaño mínimo es 1 MB.',
            'max_file_size_mb.max' => 'El tamaño máximo permitido es 1000 MB.',
            'allowed_extensions.required' => 'Las extensiones permitidas son obligatorias.',
            'image_max_width.required' => 'El ancho máximo de imagen es obligatorio.',
            'image_max_height.required' => 'El alto máximo de imagen es obligatorio.',
            'image_quality.required' => 'La calidad de imagen es obligatoria.',
            'image_quality.min' => 'La calidad mínima es 10.',
            'image_quality.max' => 'La calidad máxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'max_file_size_mb' => 'tamaño máximo de archivo',
            'allowed_extensions' => 'extensiones permitidas',
            'image_max_width' => 'ancho máximo de imagen',
            'image_max_height' => 'alto máximo de imagen',
            'image_quality' => 'calidad de imagen',
        ];
    }
}
