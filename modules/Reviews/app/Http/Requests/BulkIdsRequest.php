<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkIdsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.reviews.view');
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Debe seleccionar al menos un elemento.',
            'ids.array' => 'Los IDs deben ser un arreglo.',
            'ids.min' => 'Debe seleccionar al menos un elemento.',
        ];
    }
}
