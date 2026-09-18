<?php

namespace Modules\Helpdesk\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class UploadSimulatorAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Per-conversation authorization (token match) is enforced in the
        // controller; the public flag gates the whole feature.
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,mp3,mp4,webm,ogg,wav'],
            'token' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El elemento enviado no es un archivo válido.',
            'file.max' => 'El archivo no puede superar los 20 MB.',
            'file.mimes' => 'Tipo de archivo no permitido.',
            'token.required' => 'Falta el token de la sesión simulada.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo',
            'token' => 'token',
        ];
    }
}
