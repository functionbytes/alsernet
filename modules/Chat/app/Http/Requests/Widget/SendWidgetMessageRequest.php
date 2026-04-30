<?php

namespace Modules\Chat\Http\Requests\Widget;

use Illuminate\Foundation\Http\FormRequest;

class SendWidgetMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:10000',
            'content_type' => 'nullable|in:text,file,image,audio,video',
            'customer_id' => 'sometimes|required|integer|exists:chat_customers,id',
            'conversation_id' => 'sometimes|required|exists:chat_conversations,id',
            'message_type' => 'nullable|in:incoming,outgoing',
            'attachments' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'The message content is required.',
            'content.max' => 'The message must not exceed 10,000 characters.',
            'content_type.in' => 'The content type must be text, file, image, audio, or video.',
            'customer_id.required' => 'The customer ID is required.',
            'customer_id.exists' => 'The specified customer does not exist.',
            'conversation_id.exists' => 'The specified conversation does not exist.',
            'message_type.in' => 'The message type must be incoming or outgoing.',
            'attachments.array' => 'The attachments must be an array.',
        ];
    }
}
