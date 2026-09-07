<?php

namespace Modules\Forms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubmissionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('Forms.submissions.update')
            || $this->user()->can('Forms.submissions.index');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:new,in_review,resolved,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
