<?php

namespace Modules\Helpdesk\Http\Requests\Compliance;

use Illuminate\Foundation\Http\FormRequest;

class GdprDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.customers.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:CONFIRMAR'],
            'hard' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.required' => 'La confirmación es obligatoria.',
            'confirmation.in' => 'Debes escribir CONFIRMAR para proceder.',
        ];
    }

    public function attributes(): array
    {
        return [
            'confirmation' => 'confirmación',
            'hard' => 'eliminación permanente',
        ];
    }
}
