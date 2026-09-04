<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionFieldTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.field-types.manage')
            || $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:enable,disable'],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'exists:form_field_type_settings,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'La acción debe ser enable o disable.',
        ];
    }
}
