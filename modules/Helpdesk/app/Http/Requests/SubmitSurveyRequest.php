<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Debes responder al menos una pregunta.',
            'answers.array' => 'Las respuestas deben ser un conjunto válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'answers' => 'respuestas',
        ];
    }
}
