<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.components.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:partial,layout,component'],
            'content' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['in:active,inactive'],
            'is_default' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del componente es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'type.required' => 'El tipo del componente es obligatorio.',
            'type.in' => 'El tipo seleccionado no es válido.',
            'content.required' => 'El contenido del componente es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'type' => 'tipo',
            'content' => 'contenido',
            'description' => 'descripción',
            'status' => 'estado',
            'is_default' => 'predeterminado',
            'sort_order' => 'orden',
        ];
    }
}
