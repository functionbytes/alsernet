<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InlineUpdateSeoMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'field' => ['required', 'in:title,description,target_keyword'],
            'value' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field.required' => 'El campo es obligatorio.',
            'field.in' => 'Campo no permitido para edición rápida.',
        ];
    }
}
