<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_attributes';

    protected $fillable = [
        'attribute_set_id',
        'title',
        'slug',
        'color',
        'image',
        'is_default',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function attributeSet(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeSet::class, 'attribute_set_id');
    }
}
