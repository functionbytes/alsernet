<?php

namespace Modules\Helpdesk\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

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
        $settings = app(HelpdeskSettings::class);

        return [
            'file' => [
                'required',
                'file',
                'max:'.$settings->attachmentMaxKilobytes(),
                'mimes:'.implode(',', $settings->attachmentExtensions()),
                new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.guest_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
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
            'file.max' => 'El archivo no puede superar el límite configurado en Helpdesk.',
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
