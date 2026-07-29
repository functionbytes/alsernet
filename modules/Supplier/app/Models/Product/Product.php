<?php

namespace Modules\Supplier\Models\Product;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Supplier\Models\Ai\AiContent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Supplier\Database\Factories\Product\ProductFactory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Category\Sport;
use Modules\Supplier\Models\Category\Subfamily;
use Modules\Supplier\Models\Supplier\Supplier;

class Product extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'supplier_products';

    protected $fillable = [
        'uid',
        'erp_id',
        'supplier_id',
        'category_id',
        'erp_category_id',
        'subfamily_id',
        'erp_subfamily_id',
        'sport_id',
        'erp_sport_id',
        'code',
        'name',
        'available',
        'is_default',
        'web_published',
        'erp_model_id',
        'metadata',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_id' => 'integer',
            'supplier_id' => 'integer',
            'category_id' => 'integer',
            'erp_category_id' => 'integer',
            'subfamily_id' => 'integer',
            'erp_subfamily_id' => 'integer',
            'sport_id' => 'integer',
            'erp_sport_id' => 'integer',
            'erp_model_id' => 'integer',
            'available' => 'boolean',
            'is_default' => 'boolean',
            'web_published' => 'boolean',
            'metadata' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
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

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslation::class, 'product_id');
    }

    public function aiContent(): HasMany
    {
        return $this->hasMany(AiContent::class, 'supplier_product_id');
    }

    public function approvedContent(): HasOne
    {
        return $this->hasOne(AiContent::class, 'supplier_product_id')
            ->where('status', AiContent::STATUS_VALIDATED);
    }

    public function scopeActive($query)
    {
        return $query->where('available', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeWebPublished($query)
    {
        return $query->where('web_published', true);
    }

    public function scopeWithDetails($query)
    {
        return $query->with(['supplier', 'category', 'attributes']);
    }

    public function scopeByErpId($query, int $erpId)
    {
        return $query->where('erp_id', $erpId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }
}
