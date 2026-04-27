<?php

namespace Modules\Ecommerce\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegalPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('ecommerce.legal-page.manage');
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:100', 'unique:ecommerce_legal_pages,slug'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede superar los 100 caracteres.',
            'slug.unique' => 'Ya existe una página con este slug.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'content.required' => 'El contenido es obligatorio.',
            'meta_description.max' => 'La meta descripción no puede superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'slug',
            'title' => 'título',
            'content' => 'contenido',
            'meta_title' => 'meta título',
            'meta_description' => 'meta descripción',
            'is_published' => 'publicado',
        ];
    }
}
