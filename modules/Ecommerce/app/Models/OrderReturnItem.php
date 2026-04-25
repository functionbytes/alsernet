<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnItem extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_return_items';

    protected $fillable = [
        'order_return_id',
        'order_product_id',
        'product_id',
        'product_name',
        'qty',
        'reason',
    ];

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }
}
