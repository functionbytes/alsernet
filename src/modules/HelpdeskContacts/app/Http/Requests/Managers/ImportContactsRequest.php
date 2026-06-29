<?php

namespace Modules\HelpdeskContacts\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ImportContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('contacts.update');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo CSV.',
            'file.mimes' => 'El archivo debe ser de tipo CSV.',
            'file.max' => 'El archivo no puede superar los 5 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'archivo',
        ];
    }
}
