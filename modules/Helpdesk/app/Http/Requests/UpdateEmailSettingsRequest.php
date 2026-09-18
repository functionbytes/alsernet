<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            // Sender identity
            'email_from_name' => ['nullable', 'string', 'max:100'],
            'email_from_address' => ['nullable', 'email', 'max:150'],
            'email_reply_to' => ['nullable', 'email', 'max:150'],
            'email_signature' => ['nullable', 'string', 'max:2000'],

            // Outbound SMTP
            'outbound_enabled' => ['nullable', 'boolean'],
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl,none'],

            // Inbound IMAP
            'inbound_enabled' => ['nullable', 'boolean'],
            'imap_host' => ['nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'imap_username' => ['nullable', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'imap_encryption' => ['nullable', 'string', 'in:ssl,tls,none'],
            'imap_folder' => ['nullable', 'string', 'max:100'],
            'auto_create_tickets' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email_from_address.email' => 'El correo del remitente debe ser una dirección válida.',
            'email_reply_to.email' => 'El correo de respuesta debe ser una dirección válida.',
            'smtp_port.min' => 'El puerto SMTP debe ser mayor a 0.',
            'smtp_port.max' => 'El puerto SMTP no puede superar 65535.',
            'smtp_encryption.in' => 'El cifrado SMTP debe ser tls, ssl o none.',
            'imap_port.min' => 'El puerto IMAP debe ser mayor a 0.',
            'imap_port.max' => 'El puerto IMAP no puede superar 65535.',
            'imap_encryption.in' => 'El cifrado IMAP debe ser ssl, tls o none.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email_from_name' => 'nombre del remitente',
            'email_from_address' => 'correo del remitente',
            'email_reply_to' => 'responder a',
            'email_signature' => 'firma de email',
            'outbound_enabled' => 'email saliente',
            'smtp_host' => 'servidor SMTP',
            'smtp_port' => 'puerto SMTP',
            'smtp_username' => 'usuario SMTP',
            'smtp_password' => 'contraseña SMTP',
            'smtp_encryption' => 'cifrado SMTP',
            'inbound_enabled' => 'email entrante',
            'imap_host' => 'servidor IMAP',
            'imap_port' => 'puerto IMAP',
            'imap_username' => 'usuario IMAP',
            'imap_password' => 'contraseña IMAP',
            'imap_encryption' => 'cifrado IMAP',
            'imap_folder' => 'carpeta IMAP',
            'auto_create_tickets' => 'crear tickets automáticamente',
        ];
    }
}
