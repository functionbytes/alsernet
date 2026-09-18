<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSubmissionNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.index');
    }

    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'La nota no puede estar vacía.',
            'note.max' => 'La nota no puede superar los 5000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return ['note' => 'nota'];
    }
}
