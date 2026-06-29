<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountProduct extends Model
{
    protected $table = 'ecommerce_discount_products';

    protected $fillable = ['discount_id', 'product_id'];

    public $timestamps = false;

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
