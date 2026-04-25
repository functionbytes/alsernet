<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\ProductCategoryFactory;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_categories';

    protected static function newFactory(): ProductCategoryFactory
    {
        return ProductCategoryFactory::new();
    }

    protected $fillable = [
        'name',
        'parent_id',
        'description',
        'status',
        'order',
        'image',
        'is_featured',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_product_category');
    }
}
