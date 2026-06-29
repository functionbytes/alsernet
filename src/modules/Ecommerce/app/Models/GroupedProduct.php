<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupedProduct extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_grouped_products';

    protected $fillable = [
        'parent_product_id',
        'product_id',
        'fixed_qty',
    ];

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_product_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
