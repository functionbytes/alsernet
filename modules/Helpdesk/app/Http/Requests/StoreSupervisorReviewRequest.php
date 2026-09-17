<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupervisorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'review_type' => ['required', 'string', Rule::in([
                'approve_response',
                'approve_discount',
                'reopen_case',
                'change_policy',
            ])],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_type.required' => 'El tipo de revisión es obligatorio.',
            'review_type.in' => 'El tipo de revisión seleccionado no es válido.',
            'comment.max' => 'El comentario no puede superar los 2000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'review_type' => 'tipo de revisión',
            'comment' => 'comentario',
        ];
    }
}
