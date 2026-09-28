<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class RateAiAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'in:up,down'],
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'La valoración es obligatoria.',
            'value.in' => 'La valoración debe ser "up" o "down".',
        ];
    }
}
