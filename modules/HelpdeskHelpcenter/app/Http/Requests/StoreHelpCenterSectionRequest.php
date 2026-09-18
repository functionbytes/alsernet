<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHelpCenterSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.helpcenter.categories.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => [
                'required',
                'integer',
                'exists:helpdesk.helpdesk_helpcenter_categories,id,is_section,0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'parent_id.required' => 'La categoría padre es obligatoria.',
            'parent_id.exists' => 'La categoría padre debe ser una categoría raíz (no una sección).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'parent_id' => 'categoría padre',
        ];
    }
}
