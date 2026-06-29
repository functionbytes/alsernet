<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReplyTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.templates.update');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('review_reply_templates', 'name')
                    ->ignore($this->route('template')),
            ],
            'body' => ['sometimes', 'string', 'min:10', 'max:4000'],
            'category' => ['sometimes', 'in:positive,negative,neutral,general'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'El nombre no puede exceder 100 caracteres',
            'name.unique' => 'Este nombre de plantilla ya existe',
            'body.min' => 'El contenido debe tener al menos 10 caracteres',
            'body.max' => 'El contenido no puede exceder 4000 caracteres',
            'category.in' => 'Categoría no válida',
        ];
    }
}
