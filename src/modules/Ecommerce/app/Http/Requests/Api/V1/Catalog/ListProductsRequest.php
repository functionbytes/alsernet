<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Catalog;

use App\Http\Api\V1\BaseApiRequest;

class ListProductsRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'filter' => ['nullable', 'array'],
            'filter.brand' => ['nullable', 'string'],
            'filter.category' => ['nullable', 'string'],
            'filter.priceMin' => ['nullable', 'numeric', 'min:0'],
            'filter.priceMax' => ['nullable', 'numeric', 'min:0'],
            'filter.inStock' => ['nullable', 'boolean'],
            'filter.featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.max' => 'La búsqueda no puede superar los 120 caracteres.',
            'filter.priceMin.numeric' => 'El precio mínimo debe ser numérico.',
            'filter.priceMax.numeric' => 'El precio máximo debe ser numérico.',
            'per_page.min' => 'La paginación mínima es 1.',
            'per_page.max' => 'La paginación máxima es 100.',
        ];
    }

    // GET request — query params documented via @queryParam in CatalogController.
    public function bodyParameters(): array
    {
        return [];
    }
}
