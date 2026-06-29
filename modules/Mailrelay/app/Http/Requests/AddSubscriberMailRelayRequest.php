<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSubscriberMailRelayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.subscribers.create');
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:subscribers,email'],
            'name' => ['required', 'string', 'max:255'],
            'list_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'list_id.required' => 'El ID de lista es obligatorio.',
            'list_id.integer' => 'El ID de lista debe ser un número entero.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'name' => 'nombre',
            'list_id' => 'ID de lista',
        ];
    }
}
