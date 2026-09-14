<?php

namespace Modules\Supplier\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.templates.manage');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'template_category' => ['nullable', 'string', 'max:50'],
            'content_type' => ['required', 'string', 'in:description,short_description,title,seo_title,seo_description,seo_keywords,metadata,features,specifications,benefits'],
            'prompt_template' => ['required', 'string'],
            'output_language' => ['required', 'string', 'max:10'],
            'tone' => ['required', 'string', 'max:50'],
            'seo_focus' => ['boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre de la plantilla es obligatorio.',
            'label.max' => 'El nombre no puede superar los 255 caracteres.',
            'content_type.required' => 'El tipo de contenido es obligatorio.',
            'content_type.in' => 'El tipo de contenido seleccionado no es valido.',
            'prompt_template.required' => 'La plantilla del prompt es obligatoria.',
            'output_language.required' => 'El idioma de salida es obligatorio.',
            'tone.required' => 'El tono es obligatorio.',
            'priority.min' => 'La prioridad minima es 0.',
            'priority.max' => 'La prioridad maxima es 100.',
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'nombre',
            'template_category' => 'categoria de plantilla',
            'content_type' => 'tipo de contenido',
            'prompt_template' => 'plantilla del prompt',
            'output_language' => 'idioma de salida',
            'tone' => 'tono',
            'seo_focus' => 'enfoque SEO',
            'priority' => 'prioridad',
            'notes' => 'notas',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'seo_focus' => $this->boolean('seo_focus', false),
        ]);
    }
}
