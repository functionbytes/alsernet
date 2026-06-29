<?php

namespace Modules\HelpdeskLivechat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePreChatFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.pre-chat.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'inbox_id' => ['nullable', 'integer', 'exists:helpdesk_inboxes,id'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.type' => ['required', 'string', 'in:text,email,phone,select,textarea,checkbox'],
            'fields.*.required' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'fields.required' => 'Debes agregar al menos un campo.',
            'fields.min' => 'Debes agregar al menos un campo.',
            'fields.*.key.required' => 'La clave del campo es obligatoria.',
            'fields.*.label.required' => 'La etiqueta del campo es obligatoria.',
            'fields.*.type.required' => 'El tipo del campo es obligatorio.',
            'fields.*.type.in' => 'El tipo de campo seleccionado no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'inbox_id' => 'bandeja de entrada',
            'fields' => 'campos',
            'fields.*.key' => 'clave del campo',
            'fields.*.label' => 'etiqueta del campo',
            'fields.*.type' => 'tipo del campo',
            'fields.*.required' => 'obligatorio',
        ];
    }
}
