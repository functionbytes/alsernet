<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class ImportChatDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.view') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file_ids' => ['required', 'array', 'min:1'],
            'file_ids.*' => ['required', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file_ids.required' => 'Selecciona al menos un archivo.',
            'file_ids.array' => 'El formato de los archivos seleccionados no es válido.',
            'file_ids.min' => 'Selecciona al menos un archivo.',
            'file_ids.*.required' => 'Cada archivo seleccionado debe ser válido.',
            'file_ids.*.string' => 'Cada archivo seleccionado debe ser válido.',
            'category.max' => 'La categoría no puede superar los 120 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file_ids' => 'archivos',
            'category' => 'categoría',
        ];
    }
}
