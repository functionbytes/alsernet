<?php

namespace Modules\Supplier\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductVisibilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.view.products') ?? false;
    }

    public function rules(): array
    {
        return [
            'available' => 'sometimes|boolean',
            'web_published' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'available.boolean' => 'El campo disponible debe ser verdadero o falso.',
            'web_published.boolean' => 'El campo publicado en web debe ser verdadero o falso.',
        ];
    }
}
