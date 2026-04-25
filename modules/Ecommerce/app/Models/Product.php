<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\ProductFactory;
use Modules\Ecommerce\Enums\ProductStatus;

class Product extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_products';

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    protected $fillable = [
        'name',
        'description',
        'content',
        'status',
        'slug',
        'images',
        'featured_image',
        'sku',
        'order',
        'quantity',
        'allow_checkout_when_out_of_stock',
        'with_storehouse_management',
        'is_featured',
        'brand_id',
        'is_variation',
        'sale_type',
        'price',
        'sale_price',
        'start_date',
        'end_date',
        'length',
        'wide',
        'height',
        'weight',
        'views',
        'stock_status',
        'barcode',
        'cost_per_item',
        'minimum_order_quantity',
        'maximum_order_quantity',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'allow_checkout_when_out_of_stock' => 'boolean',
            'with_storehouse_management' => 'boolean',
            'is_featured' => 'boolean',
            'is_variation' => 'boolean',
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'length' => 'decimal:2',
            'wide' => 'decimal:2',
            'height' => 'decimal:2',
            'weight' => 'decimal:2',
            'cost_per_item' => 'decimal:2',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class)->withDefault();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'ecommerce_product_category', 'product_id', 'category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', 'approved');
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'ecommerce_product_tag_product', 'product_id', 'tag_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'ecommerce_product_collection_products', 'product_id', 'product_collection_id');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function getFinalPriceAttribute(): float
    {
        if ($this->sale_price && $this->sale_price > 0 && $this->start_date && $this->end_date && now()->between($this->start_date, $this->end_date)) {
            return (float) $this->sale_price;
        }

        return (float) $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price && $this->sale_price > 0 && $this->start_date && $this->end_date && now()->between($this->start_date, $this->end_date);
    }
}
