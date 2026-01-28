<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBounceHandlerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'type' => [
                'required',
                Rule::in(['imap', 'pop3', 'webhook']),
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'error']),
            ],
            'auto_check' => [
                'nullable',
                'boolean',
            ],
            'check_interval' => [
                'nullable',
                'integer',
                'min:5',
                'max:1440',
            ],
            'delete_after_process' => [
                'nullable',
                'boolean',
            ],
            'auto_unsubscribe_hard_bounce' => [
                'nullable',
                'boolean',
            ],
            'soft_bounce_limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
            ],
        ];

        // IMAP/POP3 specific rules
        if (in_array($this->input('type'), ['imap', 'pop3'])) {
            $rules = array_merge($rules, [
                'host' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'port' => [
                    'required',
                    'integer',
                    'min:1',
                    'max:65535',
                ],
                'protocol' => [
                    'required',
                    Rule::in(['imap', 'pop3']),
                ],
                'encryption' => [
                    'required',
                    Rule::in(['ssl', 'tls', 'none']),
                ],
                'username' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:1',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                ],
            ]);
        }

        // Webhook specific rules
        if ($this->input('type') === 'webhook') {
            $rules = array_merge($rules, [
                'webhook_token' => [
                    'required',
                    'string',
                    'min:10',
                ],
                'webhook_secret' => [
                    'nullable',
                    'string',
                    'min:10',
                ],
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del manejador es obligatorio.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'type.required' => 'Debe seleccionar un tipo de manejador.',
            'type.in' => 'El tipo de manejador seleccionado no es válido.',
            'host.required' => 'El host es obligatorio para manejadores IMAP/POP3.',
            'host.max' => 'El host no debe exceder 255 caracteres.',
            'port.required' => 'El puerto es obligatorio.',
            'port.integer' => 'El puerto debe ser un número entero.',
            'port.min' => 'El puerto debe ser mayor a 0.',
            'port.max' => 'El puerto no puede exceder 65535.',
            'protocol.required' => 'El protocolo es obligatorio.',
            'protocol.in' => 'El protocolo debe ser IMAP o POP3.',
            'encryption.required' => 'El método de encriptación es obligatorio.',
            'encryption.in' => 'El método de encriptación debe ser SSL, TLS o ninguno.',
            'username.required' => 'El nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
            'email.required' => 'La dirección de correo es obligatoria.',
            'email.email' => 'La dirección de correo debe ser válida.',
            'webhook_token.required' => 'El token del webhook es obligatorio.',
            'webhook_token.min' => 'El token debe tener al menos 10 caracteres.',
            'webhook_secret.min' => 'El secreto debe tener al menos 10 caracteres.',
            'check_interval.min' => 'El intervalo debe ser al menos 5 minutos.',
            'check_interval.max' => 'El intervalo no puede exceder 1440 minutos.',
            'soft_bounce_limit.min' => 'El límite debe ser al menos 1.',
            'soft_bounce_limit.max' => 'El límite no puede exceder 10.',
        ];
    }
}
