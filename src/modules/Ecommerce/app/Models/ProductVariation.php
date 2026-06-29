<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariation extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_variations';

    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'configurable_product_id',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function configurableProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'configurable_product_id');
    }

    public function variationItems(): HasMany
    {
        return $this->hasMany(ProductVariationItem::class, 'variation_id');
    }
}
