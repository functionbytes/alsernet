<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdeskprestashop.carts.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'attribute_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecciona un producto.',
            'quantity.required' => 'Indica la cantidad.',
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'quantity' => 'cantidad',
        ];
    }
}
