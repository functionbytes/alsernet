<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderReturn extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_returns';

    protected $fillable = [
        'order_id',
        'store_id',
        'user_id',
        'reason',
        'order_status',
        'return_status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderReturnItem::class, 'order_return_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderReturnHistory::class, 'order_return_id');
    }
}
