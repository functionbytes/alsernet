<?php

namespace Modules\Mailrelay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'name' => 'nombre',
            'metadata' => 'metadatos',
        ];
    }
}
