<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Option extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_options';

    protected $fillable = [
        'name',
        'option_type',
        'required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class, 'option_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_option_product', 'option_id', 'product_id');
    }
}
