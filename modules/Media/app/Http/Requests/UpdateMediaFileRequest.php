<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('media.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'alt.max' => 'El texto alternativo no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'alt' => 'texto alternativo',
        ];
    }
}
