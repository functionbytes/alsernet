<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $table = 'ecommerce_shipments';

    protected $fillable = [
        'order_id',
        'user_id',
        'weight',
        'shipment_id',
        'rate_id',
        'note',
        'status',
        'cod_amount',
        'cod_status',
        'cross_checking_status',
        'price',
        'store_id',
    ];

    protected function casts(): array
    {
        return [
            'cod_amount' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ShipmentHistory::class, 'shipment_id');
    }
}
