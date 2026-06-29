<?php

namespace Modules\Engagement\Http\Requests\Sdk;

use Illuminate\Foundation\Http\FormRequest;

class SetContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'context.required' => 'El contexto es obligatorio.',
            'context.array' => 'El contexto debe ser un objeto JSON.',
        ];
    }

    public function attributes(): array
    {
        return [
            'context' => 'contexto',
        ];
    }
}
