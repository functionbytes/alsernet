<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Shipment extends Model
{
    use HasFactory;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'tracking_id',
                'shipping_company_name',
                'delivered_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ecommerce_shipment');
    }

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
        'tracking_id',
        'tracking_link',
        'shipping_company_name',
        'estimate_date_shipped',
        'date_shipped',
        'delivery_token',
        'delivered_at',
        'delivered_by',
    ];

    protected function casts(): array
    {
        return [
            'cod_amount' => 'decimal:2',
            'price' => 'decimal:2',
            'estimate_date_shipped' => 'date',
            'date_shipped' => 'date',
            'delivered_at' => 'datetime',
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
