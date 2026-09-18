<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Contracts\Validation\Validator;
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
            'email' => ['nullable', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:200'],
            'custom_attributes' => ['nullable', 'array', 'max:20'],
            'custom_attributes.*' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:10240',
                // Allow common safe types. Dangerous formats (exe, html, js, php, svg)
                // are excluded because svg can embed JS and server-executed files pose RCE risk.
                'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,mp3,mp4,wav,ogg,webm',
            ],
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

        $ca = $this->input('custom_attributes');
        if (is_string($ca) && str_starts_with(trim($ca), '{')) {
            $decoded = json_decode($ca, true);
            if (is_array($decoded)) {
                $this->merge(['custom_attributes' => $decoded]);
            }
        }
    }

    public function withValidator(Validator $validator): void
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
            'attachments.*.mimes' => 'Tipo de archivo no permitido. Se aceptan: imágenes, PDF, documentos Office, audio y video.',
        ];
    }
}
