<?php

namespace Modules\Mailrelay\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // No specific permission gate on this controller
    }

    public function rules(): array
    {
        $list = $this->route('list');
        $id = is_object($list) ? $list->id : $list;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('mails_lists', 'name')->ignore($id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la lista es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'name.unique' => 'Ya existe una lista con este nombre.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
        ];
    }
}
