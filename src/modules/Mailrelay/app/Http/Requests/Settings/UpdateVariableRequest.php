<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVariableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.manage');
    }

    public function rules(): array
    {
        $id = $this->route('variable') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'variable_key' => ['required', 'string', 'max:255', Rule::unique('mailrelay_variables', 'variable_key')->ignore($id), 'regex:/^[A-Z_]+$/'],
            'category' => ['required', 'in:system,customer,order,document,custom'],
            'module' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'example_value' => ['nullable', 'string', 'max:255'],
            'status' => ['in:active,inactive'],
            'is_system' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la variable es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'variable_key.required' => 'La clave de la variable es obligatoria.',
            'variable_key.unique' => 'Esta clave de variable ya existe.',
            'variable_key.regex' => 'La clave debe contener solo letras mayúsculas y guiones bajos.',
            'category.required' => 'La categoría es obligatoria.',
            'category.in' => 'La categoría seleccionada no es válida.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'variable_key' => 'clave de variable',
            'category' => 'categoría',
            'module' => 'módulo',
            'description' => 'descripción',
            'example_value' => 'valor de ejemplo',
            'status' => 'estado',
            'is_system' => 'variable del sistema',
            'sort_order' => 'orden',
        ];
    }
}
