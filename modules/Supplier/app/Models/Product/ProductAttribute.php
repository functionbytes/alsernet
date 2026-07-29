<?php

namespace Modules\Supplier\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Supplier\Database\Factories\Product\ProductAttributeFactory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;

class ProductAttribute extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_product_attributes';

    protected $fillable = [
        'erp_id',
        'product_id',
        'category_id',
        'erp_category_id',
        'erp_group_id',
        'subfamily_id',
        'erp_subfamily_id',
        'sport_id',
        'erp_sport_id',
        'code',
        'code_secundary',
        'reference',
        'ean13',
        'upc',
        'name',
        'available',
        'web_published',
        'erp_created_at',
        'erp_updated_at',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'product_id' => 'integer',
        'category_id' => 'integer',
        'erp_category_id' => 'integer',
        'erp_group_id' => 'integer',
        'subfamily_id' => 'integer',
        'erp_subfamily_id' => 'integer',
        'sport_id' => 'integer',
        'erp_sport_id' => 'integer',
        'available' => 'boolean',
        'web_published' => 'boolean',
        'erp_created_at' => 'datetime',
        'erp_updated_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): ProductAttributeFactory
    {
        return ProductAttributeFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subfamily(): BelongsTo
    {
        return $this->belongsTo(Subfamily::class, 'subfamily_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function scopeActive($query)
    {
        return $query->where('available', true);
    }

    public function scopeWebPublished($query)
    {
        return $query->where('web_published', true);
    }

    public function scopeByErpId($query, int $erpId)
    {
        return $query->where('erp_id', $erpId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%")
                ->orWhere('code_secundary', 'LIKE', "%{$search}%")
                ->orWhere('reference', 'LIKE', "%{$search}%")
                ->orWhere('ean13', 'LIKE', "%{$search}%")
                ->orWhere('upc', 'LIKE', "%{$search}%");
        });
    }
}
