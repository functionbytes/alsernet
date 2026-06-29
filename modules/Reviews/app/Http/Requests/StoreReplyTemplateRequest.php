<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReplyTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.templates.create');
    }

    public function rules(): array
    {
        $templateId = $this->route('template')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:review_reply_templates,name,'.$templateId,
            ],
            'body' => ['required', 'string', 'min:10', 'max:4000'],
            'category' => ['required', 'in:positive,negative,neutral,general'],
            'is_active' => ['sometimes', 'boolean'],
            'review_google_location_id' => ['nullable', 'integer', 'exists:review_google_locations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la plantilla es obligatorio',
            'name.max' => 'El nombre no puede exceder 100 caracteres',
            'name.unique' => 'Este nombre de plantilla ya existe',
            'body.required' => 'El contenido de la plantilla es obligatorio',
            'body.min' => 'El contenido debe tener al menos 10 caracteres',
            'body.max' => 'El contenido no puede exceder 4000 caracteres',
            'category.required' => 'La categoría es obligatoria',
            'category.in' => 'Categoría no válida',
        ];
    }
}
