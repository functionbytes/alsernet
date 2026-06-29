<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttributeSet extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_attribute_sets';

    protected $fillable = [
        'title',
        'slug',
        'display_layout',
        'is_searchable',
        'is_comparable',
        'is_use_in_product_listing',
        'status',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_searchable' => 'boolean',
            'is_comparable' => 'boolean',
            'is_use_in_product_listing' => 'boolean',
        ];
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_set_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_product_with_attribute_set', 'attribute_set_id', 'product_id');
    }
}
