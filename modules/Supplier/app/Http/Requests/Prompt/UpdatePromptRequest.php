<?php

namespace Modules\Supplier\Http\Requests\Prompt;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('suppliers.prompts.manage');
    }

    public function rules(): array
    {
        return [
            'supplier_id'  => ['nullable', 'integer', 'exists:suppliers,id'],
            'category_id'  => ['nullable', 'integer', 'exists:supplier_categories,id'],
            'subfamily_ids'   => ['nullable', 'array'],
            'subfamily_ids.*' => ['integer', 'exists:supplier_subfamilies,id'],
            'label' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'string', 'in:global,supplier,category,supplier_category,source'],
            'content_type' => ['required', 'string', 'in:description,short_description,title,seo_title,seo_description,seo_keywords,metadata,features,specifications,benefits'],
            'prompt_template' => ['required', 'string'],
            'output_language' => ['required', 'string', 'max:10'],
            'tone' => ['required', 'string', 'max:50'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'seo_focus' => ['boolean'],
            'enable_web_search' => ['boolean'],
            'ai_model'          => ['nullable', 'string', 'max:60'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'El nombre del prompt es obligatorio.',
            'label.max' => 'El nombre no puede superar los 255 caracteres.',
            'scope.required' => 'El ambito es obligatorio.',
            'scope.in' => 'El ambito seleccionado no es valido.',
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
            'supplier_id' => 'proveedor',
            'category_id' => 'categoria',
            'label' => 'nombre',
            'scope' => 'ambito',
            'content_type' => 'tipo de contenido',
            'prompt_template' => 'plantilla del prompt',
            'output_language' => 'idioma de salida',
            'tone' => 'tono',
            'priority' => 'prioridad',
            'seo_focus' => 'enfoque SEO',
            'enable_web_search' => 'busqueda web',
            'is_active' => 'estado activo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'seo_focus' => $this->boolean('seo_focus'),
            'enable_web_search' => $this->boolean('enable_web_search'),
            'ai_model'          => $this->input('ai_model') ?: null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
