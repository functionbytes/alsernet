<?php

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload que produce JSON.stringify(subscription) sobre un
 * PushSubscription del navegador (reg.pushManager.subscribe(...) en
 * theme.blade.php): {endpoint, expirationTime, keys: {p256dh, auth}}.
 */
class SubscribePushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:2000', 'url'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'endpoint.required' => 'La suscripción no incluye un endpoint válido.',
            'keys.p256dh.required' => 'La suscripción no incluye la clave p256dh.',
            'keys.auth.required' => 'La suscripción no incluye el token auth.',
        ];
    }
}
