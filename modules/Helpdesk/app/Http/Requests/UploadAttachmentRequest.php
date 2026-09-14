<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manager.helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'max:5'],
            'files.*' => [
                'required',
                'file',
                'max:16384',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp4,mov,mp3,ogg,wav,webm,oga',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Debes seleccionar al menos un archivo.',
            'files.max' => 'No puedes adjuntar más de 5 archivos a la vez.',
            'files.*.required' => 'El archivo es requerido.',
            'files.*.file' => 'El elemento enviado no es un archivo válido.',
            'files.*.max' => 'Cada archivo no puede superar los 16 MB.',
            'files.*.mimes' => 'Tipo de archivo no permitido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'files' => 'archivos',
            'files.*' => 'archivo',
        ];
    }
}
