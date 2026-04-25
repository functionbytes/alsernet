<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_reviews';

    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'star',
        'comment',
        'status',
        'images',
    ];

    protected function casts(): array
    {
        return [
            'star' => 'integer',
            'images' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }
}
