<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewGoogleConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.connections.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la conexión es obligatorio',
            'name.max' => 'El nombre no puede exceder 100 caracteres',
        ];
    }
}
