<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ecommerce\Database\Factories\CartFactory;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_carts';

    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    protected $fillable = [
        'customer_id',
        'session_id',
        'product_id',
        'qty',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'options' => 'json',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withDefault();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withDefault();
    }
}
