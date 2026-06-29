<?php

namespace Modules\Ecommerce\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_saved_searches';

    protected $fillable = [
        'customer_id',
        'name',
        'query',
        'filters',
        'last_notified_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'last_notified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function findNewMatches(?Carbon $since = null): Collection
    {
        $query = Product::query()
            ->where('status', 'published')
            ->where('is_variation', false);

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        if ($this->query) {
            $term = $this->query;
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
            );
        }

        $filters = $this->filters ?? [];

        if (! empty($filters['brand_ids'])) {
            $query->whereIn('brand_id', $filters['brand_ids']);
        }

        if (! empty($filters['category_ids'])) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('ecommerce_product_categories.id', $filters['category_ids']));
        }

        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['on_sale'])) {
            $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price');
        }

        return $query->limit(20)->get();
    }
}
