<?php

namespace Modules\Mailrelay\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.subscribers.create');
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:mails_subscribers,email'],
            'name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:active,pending,unsubscribed,bounced,banned'],
            'metadata' => ['nullable', 'string', 'json'],
            'custom_fields' => ['nullable', 'array'],
            'mailrelay_groups' => ['nullable', 'array'],
            'mailrelay_groups.*' => ['integer', 'exists:mails_mailrelay_groups,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'status.in' => 'El estado seleccionado no es válido.',
            'metadata.json' => 'Los metadatos deben ser un JSON válido.',
            'mailrelay_groups.*.exists' => 'Uno o más grupos seleccionados no existen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'name' => 'nombre',
            'status' => 'estado',
            'metadata' => 'metadatos',
            'custom_fields' => 'campos personalizados',
            'mailrelay_groups' => 'grupos de Mailrelay',
        ];
    }
}
