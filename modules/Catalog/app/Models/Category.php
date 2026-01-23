<?php

namespace Modules\Catalog\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Modules\Prestashop\Entities\Category as PrestaShopCategory;
use Modules\Supplier\Entities\Supplier;
use Modules\Supplier\Entities\SupplierCategory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * Category Model
 *
 * Hierarchical categories with bidirectional PrestaShop synchronization
 *
 * @property int $id
 * @property string $uid ULID unique identifier
 * @property string $title Category name
 * @property int|null $parent_id Parent category ID (null for root categories)
 * @property int $position Display order within siblings
 * @property int $depth Tree depth level (auto-calculated)
 * @property string|null $slug URL-friendly identifier
 * @property string|null $meta_title SEO meta title
 * @property string|null $meta_description SEO meta description
 * @property string|null $meta_keywords SEO meta keywords
 * @property string|null $icon Font Awesome 6 icon class (fas fa-*)
 * @property string|null $color HEX color code (#90bb13)
 * @property string|null $image Image path in storage
 * @property bool $active Active status
 * @property string $visibility Visibility level (public|private|hidden)
 * @property int|null $prestashop_id PrestaShop category ID (id_category)
 * @property \Illuminate\Support\Carbon|null $last_synced_at Last sync timestamp
 * @property string $sync_direction Sync direction (laravel_to_ps|ps_to_laravel|bidirectional)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Category|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $ancestors
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Category> $descendants
 * @property-read \App\Models\PrestaShopCategoryMapping|null $prestashopMapping
 * @property-read PrestaShopCategory|null $prestashopCategory
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Supplier> $suppliers
 */
class Category extends Model
{
    use HasFactory, HasRecursiveRelationships, HasUid;

    protected $table = 'categories';

    protected $fillable = [
        'title',
        'parent_id',
        'position',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'icon',
        'color',
        'image',
        'active',
        'visibility',
        'prestashop_id',
        'last_synced_at',
        'sync_direction',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'position' => 'integer',
        'depth' => 'integer',
        'active' => 'boolean',
        'prestashop_id' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get suppliers assigned to this category (many-to-many)
     */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_categories', 'category_id', 'supplier_id')
            ->withPivot(['uid', 'is_active', 'priority', 'metadata'])
            ->withTimestamps()
            ->using(SupplierCategory::class);
    }

    /**
     * Get only active suppliers for this category
     */
    public function activeSuppliers(): BelongsToMany
    {
        return $this->suppliers()->wherePivot('is_active', true);
    }

    /**
     * Get all supplier-category pivot records
     */
    public function supplierCategories(): HasMany
    {
        return $this->hasMany(SupplierCategory::class, 'category_id');
    }

    /**
     * Get the PrestaShop category mapping
     */
    public function prestashopMapping(): HasOne
    {
        return $this->hasOne(PrestaShopCategoryMapping::class, 'category_id');
    }

    /**
     * Get the associated PrestaShop category
     */
    public function prestashopCategory(): BelongsTo
    {
        return $this->belongsTo(PrestaShopCategory::class, 'prestashop_id', 'id_category');
    }

    /**
     * Scope: Filter only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Filter by visibility
     */
    public function scopeVisible($query, ?string $visibility = 'public')
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope: Get only root categories (no parent)
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: Get only leaf categories (no children)
     */
    public function scopeLeaves($query)
    {
        return $query->doesntHave('children');
    }

    /**
     * Scope: Filter categories synced with PrestaShop
     */
    public function scopeSyncedWithPrestaShop($query)
    {
        return $query->whereNotNull('prestashop_id');
    }

    /**
     * Scope: Filter categories pending sync
     */
    public function scopePendingSync($query)
    {
        return $query->whereNull('prestashop_id')
            ->orWhere(function ($q) {
                $q->whereNotNull('prestashop_id')
                    ->where(function ($q2) {
                        $q2->whereNull('last_synced_at')
                            ->orWhereColumn('updated_at', '>', 'last_synced_at');
                    });
            });
    }

    /**
     * Scope: Order by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope: Search by title
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'LIKE', "%{$search}%");
    }

    /**
     * Accessor: Get full breadcrumb path
     */
    public function getFullPathAttribute(): string
    {
        return $this->ancestors()
            ->defaultOrder()
            ->pluck('title')
            ->push($this->title)
            ->implode(' > ');
    }

    /**
     * Accessor: Check if category is synced with PrestaShop
     */
    public function getIsSyncedAttribute(): bool
    {
        return ! is_null($this->prestashop_id) && ! is_null($this->last_synced_at);
    }

    /**
     * Accessor: Check if category needs synchronization
     */
    public function getNeedsSyncAttribute(): bool
    {
        if (is_null($this->prestashop_id)) {
            return false; // Not configured for sync
        }

        if (is_null($this->last_synced_at)) {
            return true; // Never synced
        }

        return $this->updated_at > $this->last_synced_at; // Modified after last sync
    }

    /**
     * Boot method - Register model events
     */
    protected static function booted(): void
    {
        // Auto-generate slug on create/update
        static::saving(function (Category $category) {
            // Prevent cycles
            if ($category->parent_id && $category->parent_id === $category->id) {
                throw new \InvalidArgumentException('Una categoría no puede ser su propio padre');
            }

            // Validate parent is not a descendant
            if ($category->parent_id && $category->exists) {
                $newParent = static::find($category->parent_id);
                if ($newParent && $category->isAncestorOf($newParent)) {
                    throw new \InvalidArgumentException('No se puede mover una categoría a uno de sus descendientes');
                }
            }

            // Auto-generate slug if not provided
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->title);
            }
        });
    }
}
