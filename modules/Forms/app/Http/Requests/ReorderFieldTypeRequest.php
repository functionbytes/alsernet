<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderFieldTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.field-types.manage')
            || $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:form_field_type_settings,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
