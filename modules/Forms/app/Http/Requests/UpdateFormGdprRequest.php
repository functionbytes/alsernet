<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormGdprRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.settings.manage');
    }

    public function rules(): array
    {
        return [
            'retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'anonymize_old' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'retention_days.min' => 'La retención mínima es 1 día.',
            'retention_days.max' => 'La retención máxima es 10 años (3650 días).',
        ];
    }

    public function attributes(): array
    {
        return [
            'retention_days' => 'días de retención',
        ];
    }
}
