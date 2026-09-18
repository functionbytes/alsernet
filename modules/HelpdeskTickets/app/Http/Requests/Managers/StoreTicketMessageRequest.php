<?php

namespace Modules\HelpdeskTickets\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.update') ?? false;
    }

    public function rules(): array
    {
        $settings = app(HelpdeskSettings::class);

        return [
            'body' => ['required', 'string', 'min:1', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
                Rule::prohibitedIf(fn () => ! filter_var(
                    Setting::get('tickets.user_file_upload_enable', true),
                    FILTER_VALIDATE_BOOLEAN,
                )),
            ],
            'attachments.*' => [
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
            'body.required' => 'El mensaje es obligatorio.',
            'body.min' => 'El mensaje debe tener al menos 1 caracter.',
            'body.max' => 'El mensaje no puede superar los 10000 caracteres.',
            'attachments.*.file' => 'El archivo adjunto debe ser un archivo valido.',
            'attachments.*.max' => 'El archivo adjunto no puede superar el límite configurado en Helpdesk.',
            'attachments.*.mimes' => 'El formato del archivo adjunto no es valido.',
            'attachments.max' => 'Puedes adjuntar como máximo 10 archivos.',
            'attachments.*.valid_mime_magic_bytes' => 'El contenido real del archivo adjunto no coincide con su extensión.',
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'mensaje',
            'is_internal' => 'mensaje interno',
            'attachments' => 'archivos adjuntos',
            'attachments.*' => 'archivo adjunto',
        ];
    }
}
