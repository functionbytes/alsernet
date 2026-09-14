<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForwardAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'source_url' => ['required', 'url'],
            'original_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_url.required' => 'La URL del archivo origen es obligatoria.',
            'source_url.url' => 'La URL del archivo no es válida.',
            'original_name.max' => 'El nombre del archivo no puede superar los 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'source_url' => 'URL de origen',
            'original_name' => 'nombre del archivo',
        ];
    }
}
