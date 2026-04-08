<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        $fieldTypes = array_keys(config('forms.field_types', []));

        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', $fieldTypes)],
            'key' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'validation_rules' => ['nullable', 'array'],
            'is_required' => ['boolean'],
            'width' => ['nullable', 'in:full,half,third,quarter'],
            'step_number' => ['nullable', 'integer', 'min:1'],
            'help_text' => ['nullable', 'string', 'max:500'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'step_value' => ['nullable', 'numeric'],
            'html_content' => ['nullable', 'string'],
            'consent_text' => ['nullable', 'string'],
            'formula' => ['nullable', 'string', 'max:500'],
            'mailrelay_group_id' => ['nullable', 'integer'],
            'auto_populate_param' => ['nullable', 'string', 'max:100'],
            'likert_rows' => ['nullable', 'array'],
            'show_char_counter' => ['boolean'],
            'label_position' => ['nullable', 'in:top,floating,hidden'],
        ];
    }
}
