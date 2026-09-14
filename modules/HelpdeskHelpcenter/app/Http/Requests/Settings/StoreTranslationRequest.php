<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class StoreTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.helpcenter.articles.translate') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'is_published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'locale.required' => 'El idioma es obligatorio.',
            'locale.max' => 'El código de idioma no puede superar 8 caracteres.',
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 255 caracteres.',
            'slug.required' => 'El slug es obligatorio.',
            'slug.max' => 'El slug no puede superar los 255 caracteres.',
            'meta_description.max' => 'La meta descripción no puede superar los 500 caracteres.',
            'meta_keywords.max' => 'Las palabras clave no pueden superar los 500 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'locale' => 'idioma',
            'title' => 'título',
            'slug' => 'slug',
            'body' => 'contenido',
            'meta_description' => 'meta descripción',
            'meta_keywords' => 'palabras clave',
            'is_published' => 'publicado',
        ];
    }
}
