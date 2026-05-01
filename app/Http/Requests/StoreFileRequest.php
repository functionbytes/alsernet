<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for an outgoing chat message from the agent panel.
 * Used by Modules\Chat\Http\Controllers\Helpdesk\Conversations\MessageController::store.
 */
class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:10000'],
            'private' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'El mensaje no puede superar los 10.000 caracteres.',
            'attachments.max' => 'Máximo 10 archivos por mensaje.',
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido.',
            'attachments.*.max' => 'Cada archivo no puede superar 10 MB.',
        ];
    }

    /**
     * Either content or at least one attachment must be present.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $hasContent = trim((string) $this->input('content', '')) !== '';
            $hasFiles = $this->hasFile('attachments');
            if (! $hasContent && ! $hasFiles) {
                $v->errors()->add('content', 'El mensaje no puede estar vacío.');
            }
        });
    }
}
