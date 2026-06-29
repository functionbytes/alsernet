<?php

namespace Modules\Attention\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attention.manage-notes') ?? false;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'El contenido de la nota es obligatorio.',
            'content.min' => 'La nota debe tener al menos 10 caracteres.',
            'content.max' => 'La nota no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'contenido',
        ];
    }
}
