<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SpecificationAttribute extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_specification_attributes';

    protected $fillable = [
        'group_id',
        'name',
        'type',
        'options',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(SpecificationGroup::class, 'group_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'ecommerce_product_specification_attribute', 'attribute_id', 'product_id')
            ->withPivot('value', 'hidden', 'order');
    }
}
