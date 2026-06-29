<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnHistory extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_return_histories';

    protected $fillable = [
        'order_return_id',
        'action',
        'description',
        'user_id',
    ];

    public function orderReturn(): BelongsTo
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }
}
