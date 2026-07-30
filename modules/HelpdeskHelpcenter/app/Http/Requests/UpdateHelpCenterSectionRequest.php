<?php

namespace Modules\HelpdeskHelpcenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHelpCenterSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.helpcenter.categories.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:helpdesk.helpdesk_helpcenter_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['required', 'integer', 'exists:helpdesk.helpdesk_helpcenter_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El identificador de la sección es obligatorio.',
            'id.exists' => 'La sección seleccionada no existe.',
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'parent_id.required' => 'La categoría padre es obligatoria.',
            'parent_id.exists' => 'La categoría padre seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'id' => 'sección',
            'name' => 'nombre',
            'description' => 'descripción',
            'parent_id' => 'categoría padre',
        ];
    }
}
