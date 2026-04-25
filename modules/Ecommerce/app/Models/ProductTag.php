<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductTag extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_tags';

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_product_tag_product', 'tag_id', 'product_id');
    }
}
