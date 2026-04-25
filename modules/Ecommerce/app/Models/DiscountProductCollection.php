<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountProductCollection extends Model
{
    protected $table = 'ecommerce_discount_product_collections';

    protected $fillable = ['discount_id', 'product_collection_id'];

    public $timestamps = false;

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function productCollection(): BelongsTo
    {
        return $this->belongsTo(ProductCollection::class, 'product_collection_id');
    }
}
