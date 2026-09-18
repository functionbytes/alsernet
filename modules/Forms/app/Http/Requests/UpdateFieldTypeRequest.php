<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldTypeRequest extends FormRequest
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
            'is_enabled' => ['boolean'],
            'default_css_class' => ['nullable', 'string', 'max:255'],
            'default_placeholder' => ['nullable', 'string', 'max:255'],
            'default_settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
            'group_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
