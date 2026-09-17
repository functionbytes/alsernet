<?php

namespace Modules\HelpdeskTickets\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $settings = app(HelpdeskSettings::class);

        return [
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            // ValidMimeMagicBytes además de mimes: comprueba la firma binaria
            // real del fichero, no solo lo que declara la extensión. El alta
            // interna (StoreTicketRequest) ya lo hacía; el portal — que es la
            // entrada abierta a cualquiera con un enlace mágico — se quedaba
            // en la comprobación más débil de las dos.
            'attachments.*' => [
                'nullable',
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
            'message.required' => 'El mensaje es obligatorio.',
            'message.max' => 'El mensaje no puede superar los 5000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar el límite configurado en Helpdesk.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
            'attachments.max' => 'Puedes adjuntar como máximo 10 archivos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'message' => 'mensaje',
            'attachments.*' => 'archivo adjunto',
        ];
    }
}
