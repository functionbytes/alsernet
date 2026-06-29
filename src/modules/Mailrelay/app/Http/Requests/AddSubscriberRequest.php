<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSubscriberRequest extends FormRequest
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
            'email.unique' => 'Este correo electrónico ya está suscrito.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'list_id.required' => 'Debe seleccionar una lista.',
            'list_id.integer' => 'El ID de la lista no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'name' => 'nombre',
            'list_id' => 'lista',
        ];
    }
}
