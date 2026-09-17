<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        $settings = app(HelpdeskSettings::class);

        return [
            'files' => [
                'required',
                'array',
                'max:5',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            'files.*' => [
                'required',
                'file',
                'max:'.$settings->attachmentMaxKilobytes(),
                'mimes:'.implode(',', $settings->attachmentExtensions()),
                new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
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
            'files.*.max' => 'Cada archivo no puede superar el límite configurado en Helpdesk.',
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
