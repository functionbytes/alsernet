<?php

namespace Modules\Reviews\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reviews.connections.manage');
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_active.boolean' => 'El estado debe ser un valor booleano',
        ];
    }
}
