<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ProductTag extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_product_tag_product', 'tag_id', 'product_id');
    }
}
