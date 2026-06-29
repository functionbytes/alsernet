<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkReplyIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.replies.approve');
    }

    public function rules(): array
    {
        return [
            'reply_ids' => ['required', 'array', 'max:50'],
            'reply_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply_ids.required' => 'Debe seleccionar al menos una respuesta.',
            'reply_ids.array' => 'Los IDs deben ser un arreglo.',
            'reply_ids.max' => 'No puede procesar más de 50 respuestas a la vez.',
        ];
    }
}
