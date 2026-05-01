<?php

namespace Modules\Helpdesk\Http\Requests\Widget;

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
            'content' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],
            'customer_id' => ['nullable', 'integer'],
            'customer_email' => ['nullable', 'email'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    /**
     * Accept 'message' as alias of 'content' so the widget can send either.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('content') && $this->has('message')) {
            $this->merge(['content' => (string) $this->input('message')]);
        }
    }

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

    public function messages(): array
    {
        return [
            'attachments.max' => 'Máximo 10 archivos por mensaje.',
            'attachments.*.file' => 'Cada adjunto debe ser un archivo válido.',
            'attachments.*.max' => 'Cada archivo no puede superar 10 MB.',
        ];
    }
}
