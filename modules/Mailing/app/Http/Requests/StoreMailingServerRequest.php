<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMailingServerRequest extends FormRequest
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
                Rule::in(['smtp', 'sendgrid', 'mailgun', 'ses', 'postmark', 'sparkpost', 'mailjet']),
            ],
            'from_email' => [
                'required',
                'email',
                'max:255',
            ],
            'from_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'reply_to_email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'inactive', 'error']),
            ],
            'quota_value' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'quota_unit' => [
                'nullable',
                Rule::in(['minute', 'hour', 'day']),
            ],
            'sending_limit_per_minute' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'sending_limit_per_hour' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'allow_verify_domain' => [
                'nullable',
                'boolean',
            ],
            'allow_verify_email' => [
                'nullable',
                'boolean',
            ],
            'allow_custom_return_path' => [
                'nullable',
                'boolean',
            ],
        ];

        // SMTP-specific rules
        if ($this->input('type') === 'smtp') {
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
                'encryption' => [
                    'required',
                    Rule::in(['tls', 'ssl', 'none']),
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
            ]);
        }

        // API-based services rules
        if (in_array($this->input('type'), ['sendgrid', 'mailgun', 'postmark', 'sparkpost', 'mailjet'])) {
            $rules = array_merge($rules, [
                'api_key' => [
                    'required',
                    'string',
                    'min:10',
                ],
            ]);

            // Mailgun specific
            if ($this->input('type') === 'mailgun') {
                $rules = array_merge($rules, [
                    'api_secret' => [
                        'nullable',
                        'string',
                    ],
                    'api_region' => [
                        'nullable',
                        Rule::in(['us', 'eu']),
                    ],
                ]);
            }

            // SparkPost specific
            if ($this->input('type') === 'sparkpost') {
                $rules = array_merge($rules, [
                    'api_region' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],
                ]);
            }
        }

        // AWS SES specific rules
        if ($this->input('type') === 'ses') {
            $rules = array_merge($rules, [
                'api_key' => [
                    'required',
                    'string',
                ],
                'api_secret' => [
                    'required',
                    'string',
                ],
                'api_region' => [
                    'required',
                    'string',
                    'max:255',
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
            'name.required' => 'El nombre del servidor es obligatorio.',
            'name.max' => 'El nombre del servidor no debe exceder 255 caracteres.',
            'type.required' => 'Debe seleccionar un tipo de servidor.',
            'type.in' => 'El tipo de servidor seleccionado no es válido.',
            'from_email.required' => 'El correo de envío es obligatorio.',
            'from_email.email' => 'El correo de envío debe ser una dirección de correo válida.',
            'reply_to_email.email' => 'El correo de respuesta debe ser una dirección de correo válida.',
            'host.required' => 'El host SMTP es obligatorio para servidores SMTP.',
            'host.max' => 'El host no debe exceder 255 caracteres.',
            'port.required' => 'El puerto es obligatorio para servidores SMTP.',
            'port.integer' => 'El puerto debe ser un número entero.',
            'port.min' => 'El puerto debe ser mayor a 0.',
            'port.max' => 'El puerto no puede exceder 65535.',
            'encryption.required' => 'El método de encriptación es obligatorio para servidores SMTP.',
            'encryption.in' => 'El método de encriptación seleccionado no es válido.',
            'username.required' => 'El nombre de usuario es obligatorio para servidores SMTP.',
            'password.required' => 'La contraseña es obligatoria para servidores SMTP.',
            'api_key.required' => 'La clave API es obligatoria para este tipo de servidor.',
            'api_key.min' => 'La clave API debe tener al menos 10 caracteres.',
            'api_secret.required' => 'El secreto API es obligatorio para este tipo de servidor.',
            'api_region.required' => 'La región API es obligatoria para AWS SES.',
            'api_region.in' => 'La región API debe ser "us" o "eu".',
            'quota_unit.in' => 'La unidad de cuota debe ser minuto, hora o día.',
            'sending_limit_per_minute.min' => 'El límite por minuto debe ser al menos 1.',
            'sending_limit_per_hour.min' => 'El límite por hora debe ser al menos 1.',
        ];
    }
}
