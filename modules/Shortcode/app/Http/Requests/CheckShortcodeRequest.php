<?php

namespace Modules\Shortcode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckShortcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shortcode.view') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z_][\w-]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del shortcode es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede superar los :max caracteres.',
            'name.regex' => 'El nombre solo admite letras, números, guiones y guiones bajos.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
        ];
    }
}
