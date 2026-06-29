<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.replies.approve');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo del rechazo es obligatorio.',
            'reason.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
