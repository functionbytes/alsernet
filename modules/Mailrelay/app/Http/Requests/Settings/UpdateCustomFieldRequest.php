<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.custom-fields');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mailrelay_field_id' => ['nullable', 'integer'],
            'field_type' => ['required', 'string', 'in:text,number,date,boolean,select'],
            'mapping' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string'],
            'required' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del campo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'field_type.required' => 'El tipo de campo es obligatorio.',
            'field_type.in' => 'El tipo de campo seleccionado no es válido.',
            'mapping.max' => 'El mapeo no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'mailrelay_field_id' => 'ID del campo en Mailrelay',
            'field_type' => 'tipo de campo',
            'mapping' => 'mapeo',
            'default_value' => 'valor por defecto',
            'required' => 'requerido',
        ];
    }
}
