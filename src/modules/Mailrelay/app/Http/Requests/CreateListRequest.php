<?php

namespace Modules\Mailrelay\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('mailrelay.lists.create');
    }

    public function rules(): array
    {
        return [
            'list_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'list_name.required' => 'El nombre de la lista es obligatorio.',
            'list_name.max' => 'El nombre no puede superar los 255 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'list_name' => 'nombre de la lista',
            'description' => 'descripción',
        ];
    }
}
