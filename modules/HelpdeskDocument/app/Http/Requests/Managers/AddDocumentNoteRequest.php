<?php

namespace Modules\HelpdeskDocument\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class AddDocumentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.documents.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:3'],
            'is_internal' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'La nota es obligatoria.',
            'content.min' => 'La nota debe tener al menos 3 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'content' => 'nota',
            'is_internal' => 'visibilidad',
        ];
    }
}
