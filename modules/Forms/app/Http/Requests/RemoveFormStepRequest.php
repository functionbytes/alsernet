<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveFormStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.forms.edit');
    }

    public function rules(): array
    {
        return [
            'step_number' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'step_number.required' => 'Falta indicar el número de paso.',
            'step_number.min' => 'El número de paso debe ser al menos 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'step_number' => 'número de paso',
        ];
    }
}
