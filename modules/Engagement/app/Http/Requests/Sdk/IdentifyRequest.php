<?php

namespace Modules\Engagement\Http\Requests\Sdk;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'max:128'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'attributes' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('id') && ! $this->filled('email') && ! $this->filled('phone')) {
                $validator->errors()->add('id', 'Se requiere al menos un identificador (id, email o phone).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'email.email' => 'El formato del correo electrónico no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'identificador',
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
        ];
    }
}
