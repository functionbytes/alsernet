<?php

namespace Modules\HelpdeskLivechat\Http\Requests\Widget;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Rules\ValidMimeMagicBytes;

class SendWidgetMessageRequest extends FormRequest
{
    /** Extensiones permitidas (nombre y contenido). Nunca html/svg/xml/js/php. */
    public const ALLOWED_EXTENSIONS = 'jpeg,jpg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,csv,mp3,mp4,wav,ogg,webm';

    /** Tipos MIME reales (finfo) admitidos para los adjuntos del widget. */
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.ms-excel', 'application/CDFV2',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv', 'application/csv',
        'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/wave',
        'audio/ogg', 'video/ogg', 'application/ogg', 'audio/webm', 'video/webm', 'video/mp4', 'audio/mp4',
    ];

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
                'mimes:'.self::ALLOWED_EXTENSIONS,
                // 29-sep-2026: `mimes` solo mira el contenido; `extensions` impide
                // un nombre .html/.svg con contenido de texto, y el tipo real se
                // comprueba con finfo. El nombre en disco lo genera el servidor.
                'extensions:'.self::ALLOWED_EXTENSIONS,
                new ValidMimeMagicBytes(self::ALLOWED_MIME_TYPES),
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
            'attachments.*.extensions' => 'Tipo de archivo no permitido. Se aceptan: imágenes, PDF, documentos Office, audio y video.',
        ];
    }
}
