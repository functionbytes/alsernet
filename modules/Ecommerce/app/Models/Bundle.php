<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bundle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ecommerce_bundles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_type',
        'discount_value',
        'image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_bundle_products', 'bundle_id', 'product_id')
            ->withPivot('qty')
            ->withTimestamps();
    }

    public function calculateOriginalTotal(): float
    {
        return (float) $this->products->sum(
            fn ($p) => (float) ($p->final_price ?? $p->price) * $p->pivot->qty
        );
    }

    public function calculateBundlePrice(): float
    {
        $original = $this->calculateOriginalTotal();

        if ($this->discount_type === 'percentage') {
            return max(0, $original - ($original * ((float) $this->discount_value / 100)));
        }

        return max(0, $original - (float) $this->discount_value);
    }

    public function calculateSavings(): float
    {
        return $this->calculateOriginalTotal() - $this->calculateBundlePrice();
    }
}
