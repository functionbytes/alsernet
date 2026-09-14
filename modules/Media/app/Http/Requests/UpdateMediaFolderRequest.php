<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'color.regex' => 'El color debe tener formato hexadecimal válido (#RRGGBB).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'color' => 'color',
        ];
    }
}
