<?php

namespace Modules\Shortcode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompileShortcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shortcode.view') === true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'El contenido es obligatorio.',
            'content.string' => 'El contenido debe ser texto.',
            'content.max' => 'El contenido no puede superar los :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'contenido',
        ];
    }
}
