<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestReplyApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.replies.create');
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.max' => 'La nota no puede superar los 500 caracteres.',
        ];
    }
}
