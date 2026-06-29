<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VelocityDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reviews.view');
    }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'exists:review_google_locations,id'],
            'days' => ['nullable', 'integer', 'in:30,60,90'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'La ubicación seleccionada no existe.',
            'days.in' => 'Los días deben ser 30, 60 o 90.',
        ];
    }
}
