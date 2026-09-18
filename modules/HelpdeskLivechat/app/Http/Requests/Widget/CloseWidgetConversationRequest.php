<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class CloseWidgetConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer'],
            'customer_email' => ['nullable', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer' => 'La valoración debe ser un número entero.',
            'rating.min' => 'La valoración mínima es 1.',
            'rating.max' => 'La valoración máxima es 5.',
            'feedback.max' => 'El comentario no puede superar los 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => 'valoración',
            'feedback' => 'comentario',
            'customer_email' => 'correo del cliente',
        ];
    }
}
