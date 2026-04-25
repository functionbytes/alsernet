<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAddress extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_order_addresses';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'country',
        'state',
        'city',
        'address',
        'order_id',
        'type',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
