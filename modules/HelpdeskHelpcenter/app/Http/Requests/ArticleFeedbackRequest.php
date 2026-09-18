<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'helpful' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'helpful.required' => 'El campo de utilidad es obligatorio.',
            'helpful.boolean' => 'El campo de utilidad debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'helpful' => 'utilidad',
        ];
    }
}
