<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_product_views';

    protected $fillable = [
        'product_id',
        'views',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
