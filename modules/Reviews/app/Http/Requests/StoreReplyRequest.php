<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Reviews\Models\Review;

class StoreReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $status = $this->input('status', 'draft');

        return match ($status) {
            'draft' => $this->user()->can('reviews.replies.create'),
            'approved' => $this->user()->can('reviews.replies.approve'),
            'published' => $this->user()->can('reviews.replies.publish'),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'review_id' => ['required', 'exists:reviews,id'],
            'reply_text' => ['required', 'string', 'min:10', 'max:4000'],
            'status' => ['required', 'in:draft,approved,published'],
            'template_id' => ['sometimes', 'nullable', 'exists:review_reply_templates,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_id.required' => 'El ID de la reseña es obligatorio',
            'review_id.exists' => 'La reseña no existe',
            'reply_text.required' => 'El texto de la respuesta es obligatorio',
            'reply_text.min' => 'La respuesta debe tener al menos 10 caracteres',
            'reply_text.max' => 'La respuesta no puede exceder 4000 caracteres',
            'status.required' => 'El estado es obligatorio',
            'status.in' => 'Estado no válido',
            'template_id.exists' => 'La plantilla no existe',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $status = $this->input('status');
            $review = Review::find($this->input('review_id'));

            if ($status === 'published' && $review && $review->reply && $review->reply->isPublished()) {
                $validator->errors()->add('review_id', 'Esta reseña ya tiene una respuesta publicada');
            }
        });
    }
}
