<?php

namespace Modules\EcommercePayment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WompiSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:0,1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'mode' => ['required', 'in:sandbox,production'],
            'public_key' => ['required', 'string'],
            'private_key' => ['required', 'string'],
            'integrity_secret' => ['required', 'string'],
            'event_secret' => ['required', 'string'],
            'notification_email' => ['nullable', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser activo o inactivo.',
            'name.required' => 'El nombre es obligatorio.',
            'mode.required' => 'El modo es obligatorio.',
            'mode.in' => 'El modo debe ser sandbox o production.',
            'public_key.required' => 'La Public Key es obligatoria.',
            'private_key.required' => 'La Private Key es obligatoria.',
            'integrity_secret.required' => 'El Integrity Secret es obligatorio.',
            'event_secret.required' => 'El Event Secret es obligatorio.',
            'notification_email.email' => 'El email de notificacion debe ser valido.',
        ];
    }
}
