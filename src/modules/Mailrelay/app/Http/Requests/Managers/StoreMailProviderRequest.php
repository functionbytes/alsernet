<?php

namespace Modules\Mailrelay\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreMailProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', 'in:mailrelay,mailtrap'],
            'credentials' => ['required', 'array'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'priority' => ['integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'driver.required' => 'El driver del proveedor es obligatorio.',
            'driver.in' => 'El driver seleccionado no es válido.',
            'credentials.required' => 'Las credenciales son obligatorias.',
            'priority.min' => 'La prioridad no puede ser menor a 0.',
            'priority.max' => 'La prioridad no puede superar 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'driver' => 'driver',
            'credentials' => 'credenciales',
            'is_active' => 'activo',
            'is_default' => 'predeterminado',
            'priority' => 'prioridad',
        ];
    }
}
