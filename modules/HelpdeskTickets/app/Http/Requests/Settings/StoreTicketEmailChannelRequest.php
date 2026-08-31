<?php

namespace Modules\HelpdeskTickets\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Modules\HelpdeskTickets\Support\TicketEmailChannelUrlGuard;

class StoreTicketEmailChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.tickets.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (! TicketEmailChannelUrlGuard::isHostAllowed($value)) {
                    $fail('El servidor IMAP no está permitido (apunta a una IP interna/reservada no válida).');
                }
            }],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'folder' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl'],
            'create_tickets' => ['nullable', 'boolean'],
            'create_replies' => ['nullable', 'boolean'],
            // SMTP saliente: mismo usuario/contraseña que IMAP (un solo buzón),
            // solo cambia servidor/puerto/encriptación — así las respuestas del
            // agente salen desde esta misma cuenta en vez del mailer global.
            'smtp_host' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && ! TicketEmailChannelUrlGuard::isHostAllowed($value)) {
                    $fail('El servidor SMTP no está permitido (apunta a una IP interna/reservada no válida).');
                }
            }],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['nullable', 'string', 'in:tls,ssl'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del canal es obligatorio.',
            'host.required' => 'El servidor IMAP es obligatorio.',
            'port.required' => 'El puerto es obligatorio.',
            'port.integer' => 'El puerto debe ser un número.',
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'encryption.in' => 'La encriptación debe ser TLS o SSL.',
            'smtp_port.integer' => 'El puerto SMTP debe ser un número.',
            'smtp_encryption.in' => 'La encriptación SMTP debe ser TLS o SSL.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'host' => 'servidor',
            'port' => 'puerto',
            'username' => 'usuario',
            'password' => 'contraseña',
            'folder' => 'carpeta',
            'encryption' => 'encriptación',
            'smtp_host' => 'servidor SMTP',
            'smtp_port' => 'puerto SMTP',
            'smtp_encryption' => 'encriptación SMTP',
        ];
    }
}
