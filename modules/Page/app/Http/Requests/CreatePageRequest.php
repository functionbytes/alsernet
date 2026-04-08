<?php

namespace Modules\Page\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Page\Enums\PageStatus;
use Modules\Page\Models\Page;
use Modules\Page\Models\PageTranslation;

class CreatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:pages,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:pages,slug',
                'regex:/^[\p{L}0-9]+(?:[\/\-][\p{L}0-9]+)*$/u',
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
                    PageStatus::Draft->value,
                    PageStatus::Published->value,
                    PageStatus::Pending->value,
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
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:page_categories,id'],
            'tags_input' => ['nullable', 'string', 'max:500'],
            'translations' => ['nullable', 'array'],
            'translations.*.title' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}0-9]+(?:[\/\-][\p{L}0-9]+)*$/u'],
            'translations.*.content' => ['nullable', 'string'],
            'translations.*.description' => ['nullable', 'string', 'max:500'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:255'],
            'translations.*.seo_description' => ['nullable', 'string', 'max:500'],
            'translations.*.seo_keywords' => ['nullable', 'string', 'max:500'],
            'translations.*.seo_image_url' => ['nullable', 'string', 'max:500'],
            'translations.*.seo_noindex' => ['nullable', 'boolean'],
            'translations.*.status' => ['nullable', Rule::in([PageStatus::Draft->value, PageStatus::Published->value, PageStatus::Pending->value])],
            'translations.*.published_at' => ['nullable', 'date'],
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
            'slug.regex' => 'El slug solo puede contener letras, números, guiones (-) y barras (/).',
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
        // Derive top-level title from the first locale's translation if not sent directly
        $translations = $this->input('translations', []);
        $firstLocale = array_key_first($translations);
        $firstTitle = $translations[$firstLocale]['title'] ?? null;

        if (empty($this->title) && $firstTitle) {
            $this->merge(['title' => $firstTitle]);
        }

        // Auto-generate slug from title if not provided
        $effectiveTitle = $this->title ?? $firstTitle;
        if ((! $this->has('slug') || empty($this->slug)) && $effectiveTitle) {
            $this->merge([
                'slug' => Str::slug($effectiveTitle),
            ]);
        }

        // Set published_at to now if publishing
        if ($this->status === PageStatus::Published->value && ! $this->has('published_at')) {
            $this->merge(['published_at' => now()]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            foreach ($this->input('translations', []) as $locale => $data) {
                $slug = $data['slug'] ?? null;
                if (! $slug) {
                    continue;
                }

                $exists = PageTranslation::forLocale($locale)
                    ->where('slug', $slug)
                    ->exists();

                if ($exists) {
                    $v->errors()->add(
                        "translations.{$locale}.slug",
                        "El slug '{$slug}' ya está en uso para el idioma {$locale}."
                    );
                }
            }
        });
    }
}
