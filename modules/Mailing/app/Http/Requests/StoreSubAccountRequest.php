<?php

namespace Modules\Mailing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubAccountRequest extends FormRequest
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
        return [
            'sending_server_id' => [
                'required',
                'integer',
                'exists:mailing_sending_servers,id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
            ],
            'api_key' => [
                'nullable',
                'string',
                'min:10',
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'suspended', 'inactive']),
            ],
            'can_create_campaigns' => [
                'nullable',
                'boolean',
            ],
            'can_manage_subscribers' => [
                'nullable',
                'boolean',
            ],
            'can_view_reports' => [
                'nullable',
                'boolean',
            ],
            'can_create_sending_servers' => [
                'nullable',
                'boolean',
            ],
            'email_quota_per_day' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'email_quota_per_month' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'subscriber_quota' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'campaign_quota' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'string',
                'max:255',
            ],
            'allowed_sending_server_ids' => [
                'nullable',
                'array',
            ],
            'allowed_sending_server_ids.*' => [
                'integer',
                'exists:mailing_sending_servers,id',
            ],
            'allowed_list_ids' => [
                'nullable',
                'array',
            ],
            'allowed_list_ids.*' => [
                'integer',
                'exists:mailrelay_lists,id',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sending_server_id.required' => 'Debe seleccionar un servidor de envío.',
            'sending_server_id.exists' => 'El servidor de envío seleccionado no existe.',
            'name.required' => 'El nombre de la subcuenta es obligatorio.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'description.max' => 'La descripción no debe exceder 1000 caracteres.',
            'email.email' => 'La dirección de correo debe ser válida.',
            'email.max' => 'La dirección de correo no debe exceder 255 caracteres.',
            'username.max' => 'El nombre de usuario no debe exceder 255 caracteres.',
            'api_key.min' => 'La clave API debe tener al menos 10 caracteres.',
            'status.in' => 'El estado debe ser activo, suspendido o inactivo.',
            'email_quota_per_day.min' => 'La cuota diaria debe ser mayor o igual a 0.',
            'email_quota_per_month.min' => 'La cuota mensual debe ser mayor o igual a 0.',
            'subscriber_quota.min' => 'La cuota de suscriptores debe ser mayor o igual a 0.',
            'campaign_quota.min' => 'La cuota de campañas debe ser mayor o igual a 0.',
            'permissions.array' => 'Los permisos deben ser un arreglo.',
            'allowed_sending_server_ids.array' => 'Los servidores permitidos deben ser un arreglo.',
            'allowed_sending_server_ids.*.exists' => 'Uno o más servidores de envío seleccionados no existen.',
            'allowed_list_ids.array' => 'Las listas permitidas deben ser un arreglo.',
            'allowed_list_ids.*.exists' => 'Una o más listas seleccionadas no existen.',
        ];
    }
}
