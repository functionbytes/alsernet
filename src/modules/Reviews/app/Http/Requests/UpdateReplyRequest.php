<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reviews\Models\ReviewReply;

class UpdateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reply = $this->route('reply');

        if (! $reply instanceof ReviewReply) {
            $reply = ReviewReply::findOrFail($reply);
        }

        return $this->user()?->id === $reply->created_by || $this->user()->can('reviews.replies.update');
    }

    public function rules(): array
    {
        return [
            'reply_text' => ['sometimes', 'string', 'min:10', 'max:4000'],
            'status' => ['sometimes', 'in:draft,approved,published,failed'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply_text.min' => 'La respuesta debe tener al menos 10 caracteres',
            'reply_text.max' => 'La respuesta no puede exceder 4000 caracteres',
            'status.in' => 'Estado no válido para la respuesta',
        ];
    }
}
