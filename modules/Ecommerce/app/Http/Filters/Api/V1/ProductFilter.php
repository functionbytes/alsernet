<?php

namespace Modules\Ecommerce\Http\Filters\Api\V1;

use App\Http\Api\V1\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends QueryFilter
{
    protected array $allowedFilters = ['brand', 'category', 'priceMin', 'priceMax', 'inStock', 'featured'];

    protected array $allowedSorts = ['createdAt', 'price', 'name'];

    protected array $allowedIncludes = ['brand', 'categories', 'reviews'];

    protected function filterBrand(Builder $query, string $value): void
    {
        $query->whereHas('brand', fn (Builder $q) => $q->where('slug', $value));
    }

    protected function filterCategory(Builder $query, string $value): void
    {
        $query->whereHas('categories', fn (Builder $q) => $q->where('slug', $value));
    }

    protected function filterPriceMin(Builder $query, mixed $value): void
    {
        $query->where('price', '>=', (float) $value);
    }

    protected function filterPriceMax(Builder $query, mixed $value): void
    {
        $query->where('price', '<=', (float) $value);
    }

    protected function filterInStock(Builder $query, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('quantity', '>', 0);
        }
    }

    protected function filterFeatured(Builder $query, mixed $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_featured', true);
        }
    }
}
