<?php

namespace Modules\Mailrelay\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.settings.groups');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mailrelay_group_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'sync_enabled' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'mailrelay_group_id' => 'ID del grupo en Mailrelay',
            'description' => 'descripción',
            'sync_enabled' => 'sincronización habilitada',
        ];
    }
}
