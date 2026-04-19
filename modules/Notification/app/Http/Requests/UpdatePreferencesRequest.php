<?php

namespace Modules\Notification\Http\Requests;

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
            'preferences.*.notification_type' => ['required', 'string'],
            'preferences.*.channel' => ['required', 'string', 'in:mail,database,broadcast'],
            'preferences.*.enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'preferences.required' => 'Las preferencias son obligatorias.',
            'preferences.array' => 'Las preferencias deben ser un arreglo.',
            'preferences.*.notification_type.required' => 'El tipo de notificación es obligatorio.',
            'preferences.*.channel.required' => 'El canal es obligatorio.',
            'preferences.*.channel.in' => 'El canal debe ser: correo, base de datos o broadcast.',
        ];
    }

    public function attributes(): array
    {
        return [
            'preferences' => 'preferencias',
            'preferences.*.notification_type' => 'tipo de notificación',
            'preferences.*.channel' => 'canal',
            'preferences.*.enabled' => 'habilitado',
        ];
    }
}
