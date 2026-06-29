<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariationItem extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_variation_items';

    protected $fillable = [
        'attribute_id',
        'variation_id',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'attribute_id');
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }
}
