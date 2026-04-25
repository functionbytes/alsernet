<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ecommerce\Database\Factories\BrandFactory;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_brands';

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website',
        'logo',
        'status',
        'order',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
