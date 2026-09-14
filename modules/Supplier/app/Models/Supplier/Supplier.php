<?php

namespace Modules\Supplier\Models\Supplier;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Supplier\Database\Factories\Supplier\SupplierFactory;
use Modules\Supplier\Models\Category\Category;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Models\Source\Source;

class Supplier extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'erp_id',
        'code',
        'label',
        'description',
        'cif',
        'email',
        'available',
        'priority',
        'metadata',
        'erp_created_at',
        'erp_updated_at',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_id' => 'integer',
            'available' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
            'erp_created_at' => 'datetime',
            'erp_updated_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'supplier_supplier_categories', 'supplier_id', 'category_id')
            ->withPivot(['uid', 'is_primary', 'is_active', 'priority', 'metadata'])
            ->withTimestamps()
            ->using(SupplierCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function prompts(): HasMany
    {
        return $this->hasMany(Prompt::class, 'supplier_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class, 'supplier_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('available', true);
    }

    public function scopeByErpId(Builder $query, int $erpId): Builder
    {
        return $query->where('erp_id', $erpId);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('label', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    public function scopeByUid(Builder $query, string $uid): Builder
    {
        return $query->where('uid', $uid);
    }
}
