<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResendSubmissionEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.update');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:admin,client'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de email debe ser admin o client.',
        ];
    }
}
