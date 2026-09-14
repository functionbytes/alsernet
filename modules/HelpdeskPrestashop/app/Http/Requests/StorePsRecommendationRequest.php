<?php

namespace Modules\HelpdeskPrestashop\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Conversation;

class StorePsRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation
            && ($this->user()?->can('view', $conversation) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'id_product_attribute' => ['nullable', 'integer', 'min:1'],
            'product_name' => ['required', 'string', 'max:255'],
            'product_sku' => ['nullable', 'string', 'max:100'],
            'price_with_tax' => ['nullable', 'numeric', 'min:0'],
            'product_url' => ['nullable', 'string', 'max:500'],
            'product_image' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'El producto es obligatorio.',
            'product_name.required' => 'El nombre del producto es obligatorio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'producto',
            'product_name' => 'nombre del producto',
            'price_with_tax' => 'precio',
        ];
    }
}
