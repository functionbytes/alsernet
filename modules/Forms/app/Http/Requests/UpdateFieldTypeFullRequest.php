<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldTypeFullRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.field-types.manage')
            || $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['required', 'string', 'max:100'],
            'group_name' => ['required', 'string', 'max:100'],
            'group_order' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_enabled' => ['boolean'],
            'default_css_class' => ['nullable', 'string', 'max:255'],
            'default_placeholder' => ['nullable', 'string', 'max:255'],
            'custom_css' => ['nullable', 'string'],
            'custom_html' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => 'etiqueta',
            'icon' => 'icono',
            'group_name' => 'grupo',
            'group_order' => 'orden del grupo',
            'sort_order' => 'orden',
            'is_enabled' => 'activo',
        ];
    }
}
