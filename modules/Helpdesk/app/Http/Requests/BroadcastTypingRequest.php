<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastTypingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_typing' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_typing.boolean' => 'El indicador de escritura debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'is_typing' => 'indicador de escritura',
        ];
    }
}
