<?php

namespace Modules\Remarketing\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSegmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.segments.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'string', 'in:static,dynamic'],
            'conditions' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo debe ser static o dynamic.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'type' => 'tipo',
            'conditions' => 'condiciones',
        ];
    }
}
