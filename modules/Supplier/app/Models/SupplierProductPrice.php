<?php

namespace Modules\Supplier\Models;

use App\Traits\HasUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Supplier\Database\Factories\SupplierProductPriceFactory;
use Modules\Supplier\Models\Product\Product;

/**
 * Supplier product price record.
 *
 * Each row represents a single price tier for a provider product (relationship
 * managed via the parent supplier_products row through provider_product_id).
 * The "current" record (is_current=true) is the price applied today; older
 * rows preserve historical cost for audit and ERP reconciliation.
 *
 * @property int $id
 * @property string $uid
 * @property int $provider_product_id
 * @property int|null $erp_price_id
 * @property int|null $erp_artiprov_id
 * @property float $cost
 * @property float $discount1
 * @property float $discount2
 * @property float $final_cost
 * @property Carbon $effective_date
 * @property bool $is_active
 * @property bool $is_current
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $erp_updated_at
 */
class SupplierProductPrice extends Model
{
    use HasFactory, HasUid, SoftDeletes;

    protected $table = 'supplier_product_prices';

    protected static function newFactory(): SupplierProductPriceFactory
    {
        return SupplierProductPriceFactory::new();
    }

    protected $fillable = [
        'uid',
        'provider_product_id',
        'erp_price_id',
        'erp_artiprov_id',
        'cost',
        'discount1',
        'discount2',
        'final_cost',
        'effective_date',
        'is_active',
        'is_current',
        'last_synced_at',
        'erp_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:4',
            'discount1' => 'decimal:3',
            'discount2' => 'decimal:3',
            'final_cost' => 'decimal:4',
            'effective_date' => 'date',
            'is_active' => 'boolean',
            'is_current' => 'boolean',
            'last_synced_at' => 'datetime',
            'erp_updated_at' => 'datetime',
        ];
    }

    /**
     * Provider product association. Currently maps to supplier_products.id —
     * if a dedicated provider_products table is introduced later, point the
     * relationship there without changing the column name.
     */
    public function providerProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'provider_product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Recalculate the chained discount price: cost * (1-d1/100) * (1-d2/100).
     */
    public function calculateFinalCost(): float
    {
        $cost = (float) $this->cost;
        $d1 = (float) $this->discount1;
        $d2 = (float) $this->discount2;

        return round($cost * (1 - $d1 / 100) * (1 - $d2 / 100), 4);
    }
}
