<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'parent_id.exists' => 'La carpeta padre seleccionada no existe.',
            'color.regex' => 'El color debe tener formato hexadecimal válido (#RRGGBB).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'parent_id' => 'carpeta padre',
            'color' => 'color',
        ];
    }
}
