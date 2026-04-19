<?php

namespace Modules\Notification\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['required', 'array'],
            'preferences.*.channel' => ['required', 'string', 'in:in_app,push,email,sms'],
            'preferences.*.type' => ['required', 'string'],
            'preferences.*.enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferences.required' => 'Las preferencias son obligatorias.',
            'preferences.array' => 'Las preferencias deben ser un arreglo.',
            'preferences.*.channel.required' => 'El canal es obligatorio.',
            'preferences.*.channel.in' => 'El canal debe ser: in_app, push, email o sms.',
            'preferences.*.type.required' => 'El tipo es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'preferences' => 'preferencias',
            'preferences.*.channel' => 'canal',
            'preferences.*.type' => 'tipo',
            'preferences.*.enabled' => 'habilitado',
        ];
    }
}
