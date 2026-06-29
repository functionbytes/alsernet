<?php

namespace Modules\Newsletter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Captcha\Facades\Captcha;
use Modules\Core\Models\Setting;

class PublicSubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'email' => ['required', 'email', 'max:191'],
            'name' => ['nullable', 'string', 'max:191'],
        ];

        if (Setting::get('newsletter.recaptcha_enabled') === '1' && Captcha::isEnabled()) {
            $rules = array_merge($rules, Captcha::rules());
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 191 caracteres.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los 191 caracteres.',
            'g-recaptcha-response.required' => 'Debe completar la verificación de captcha.',
            'g-recaptcha-response.captcha' => 'La verificación de captcha falló. Inténtelo nuevamente.',
        ];
    }
}
