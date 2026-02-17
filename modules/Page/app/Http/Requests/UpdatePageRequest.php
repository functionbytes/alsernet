<?php

namespace Modules\Page\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Page\Models\Page;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajustar según políticas de autorización
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $pageId = $this->route('page') ? $this->route('page')->id : $this->route('id');

        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($pageId),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'content' => [
                'nullable',
                'string',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'template' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('page.templates', ['default' => 'Default']))),
            ],
            'status' => [
                'required',
                Rule::in([
                    Page::STATUS_DRAFT,
                    Page::STATUS_PUBLISHED,
                    Page::STATUS_PENDING,
                ]),
            ],
            'published_at' => [
                'nullable',
                'date',
            ],
            'publish_at' => [
                'nullable',
                'date',
                'after_or_equal:now',
            ],
            'unpublish_at' => [
                'nullable',
                'date',
                'after:publish_at',
            ],
            'seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'seo_description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'seo_keywords' => [
                'nullable',
                'string',
                'max:500',
            ],
            'header_style' => [
                'nullable',
                'string',
                Rule::in(['header-style-1', 'header-style-2', 'header-style-3', 'header-style-4']),
            ],
            'seo_noindex' => [
                'nullable',
                'boolean',
            ],
            'featured_image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:2048',
            ],
            'seo_image_url' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'slug' => 'slug',
            'content' => 'contenido',
            'description' => 'descripción',
            'template' => 'plantilla',
            'status' => 'estado',
            'published_at' => 'fecha de publicación',
            'publish_at' => 'fecha de publicación programada',
            'unpublish_at' => 'fecha de despublicación programada',
            'seo_title' => 'título SEO',
            'seo_description' => 'descripción SEO',
            'seo_keywords' => 'palabras clave SEO',
            'header_style' => 'estilo de encabezado',
            'seo_noindex' => 'noindex',
            'featured_image' => 'imagen destacada',
            'seo_image_url' => 'URL imagen SEO',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede exceder :max caracteres.',
            'slug.unique' => 'Este slug ya está en uso.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado seleccionado no es válido.',
            'template.in' => 'La plantilla seleccionada no es válida.',
            'featured_image.image' => 'El archivo debe ser una imagen.',
            'featured_image.mimes' => 'La imagen debe ser de tipo: :values.',
            'featured_image.max' => 'La imagen no puede ser mayor a 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug from title if not provided
        if (! $this->has('slug') || empty($this->slug)) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->title),
            ]);
        }

        // Set published_at to now if publishing and not already set
        if ($this->status === Page::STATUS_PUBLISHED && ! $this->has('published_at')) {
            $page = $this->route('page');
            if (! $page || ! $page->published_at) {
                $this->merge([
                    'published_at' => now(),
                ]);
            }
        }
    }
}
