<?php

namespace Modules\Database\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TruncateTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('database.cleanup.truncate') ?? false;
    }

    public function rules(): array
    {
        return [
            'tables' => ['required', 'array', 'min:1', 'max:50'],
            'tables.*' => ['required', 'string', 'regex:/^[a-zA-Z0-9_]+$/'],
            'password' => ['required', 'string', 'current_password:web'],
            'confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'tables.required' => 'Debes seleccionar al menos una tabla.',
            'tables.max' => 'No puedes vaciar más de 50 tablas a la vez.',
            'tables.*.regex' => 'Nombre de tabla inválido.',
            'password.required' => 'Debes confirmar tu contraseña.',
            'password.current_password' => 'La contraseña no es correcta.',
            'confirmed.accepted' => 'Debes confirmar que entiendes los riesgos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'tables' => 'tablas',
            'password' => 'contraseña',
            'confirmed' => 'confirmación',
        ];
    }
}
