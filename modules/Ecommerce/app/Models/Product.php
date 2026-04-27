<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Modules\Ecommerce\Database\Factories\ProductFactory;
use Modules\Ecommerce\Enums\ProductStatus;
use Modules\Ecommerce\Traits\HasTranslations;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;
    use LogsActivity;
    use Searchable;

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
        'label_id',
        'product_type',
        'meta_title',
        'meta_description',
        'is_subscription',
        'subscription_interval',
        'subscription_discount_percent',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'sku',
                'price',
                'sale_price',
                'quantity',
                'status',
                'brand_id',
                'meta_title',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ecommerce_product');
    }

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'images' => 'array',
            'product_type' => 'string',
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

    public function searchableAs(): string
    {
        return 'ecommerce_products';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku ?? '',
            'description' => strip_tags($this->description ?? ''),
            'meta_title' => $this->meta_title ?? '',
            'meta_description' => $this->meta_description ?? '',
            'brand_name' => $this->brand?->name ?? '',
            'category_names' => $this->categories->pluck('name')->implode(' '),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === ProductStatus::PUBLISHED && ! $this->is_variation;
    }

    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['brand', 'categories']);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProductFile::class);
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

    public function label(): BelongsTo
    {
        return $this->belongsTo(ProductLabel::class)->withDefault();
    }

    public function attributeSets(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeSet::class, 'ecommerce_product_with_attribute_set', 'product_id', 'attribute_set_id');
    }

    public function specificationTables(): BelongsToMany
    {
        return $this->belongsToMany(SpecificationTable::class, 'ecommerce_product_specification_tables', 'product_id', 'table_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function crossSales(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'ecommerce_product_cross_sale_relations', 'from_product_id', 'to_product_id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(static::class, 'ecommerce_product_related_relations', 'from_product_id', 'to_product_id');
    }

    public function specificationAttributes(): BelongsToMany
    {
        return $this->belongsToMany(SpecificationAttribute::class, 'ecommerce_product_specification_attribute', 'product_id', 'attribute_id')
            ->withPivot(['value', 'hidden', 'order']);
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
