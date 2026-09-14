<?php

namespace Modules\Supplier\Models\Category;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Supplier\Database\Factories\Category\CategoryFactory;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Supplier\Supplier;

class Category extends Model
{
    use HasFactory;

    protected $table = 'supplier_categories';

    protected $fillable = [
        'erp_id',
        'sport_id',
        'erp_sport_id',
        'erp_categoria_id',
        'erp_categoria_name',
        'name',
        'short_name',
        'available',
        'erp_created_at',
        'erp_updated_at',
        'last_sync_at',
    ];

    protected $casts = [
        'erp_id' => 'integer',
        'sport_id' => 'integer',
        'erp_sport_id' => 'integer',
        'available' => 'boolean',
        'erp_created_at' => 'datetime',
        'erp_updated_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class, 'sport_id');
    }

    public function subfamilies(): HasMany
    {
        return $this->hasMany(Subfamily::class, 'category_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_supplier_categories', 'category_id', 'supplier_id')
            ->withPivot(['uid', 'is_primary', 'is_active', 'priority', 'metadata'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('available', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
            ->orWhere('short_name', 'LIKE', "%{$search}%");
    }
}
