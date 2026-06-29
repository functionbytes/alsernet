<?php

namespace Modules\Helpdesk\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleFeedbackRequest extends FormRequest
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
            'helpful.required' => 'El valor de utilidad es obligatorio.',
            'helpful.boolean' => 'El valor de utilidad debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'helpful' => 'útil',
        ];
    }
}
