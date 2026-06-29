<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reviews.view');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:SPAM,FAKE_REVIEW,HATE_SPEECH,HARASSMENT,OTHER'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'El motivo del reporte es obligatorio.',
            'reason.in' => 'El motivo seleccionado no es válido.',
        ];
    }
}
