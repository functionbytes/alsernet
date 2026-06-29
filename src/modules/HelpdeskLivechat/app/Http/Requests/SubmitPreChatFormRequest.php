<?php

namespace Modules\HelpdeskLivechat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitPreChatFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'integer'],
            'data' => ['required', 'array'],
            'customer_id' => ['nullable', 'integer'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'conversation_id.required' => 'La conversación es obligatoria.',
            'data.required' => 'Los datos del formulario son obligatorios.',
        ];
    }

    public function attributes(): array
    {
        return [
            'conversation_id' => 'conversación',
            'data' => 'datos del formulario',
        ];
    }
}
