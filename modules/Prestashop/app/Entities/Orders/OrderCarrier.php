<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Carrier;

class OrderCarrier extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_carrier';

    protected $primaryKey = 'id_order_carrier';

    public $timestamps = false;

    protected $fillable = [
        'id_order',
        'id_carrier',
        'id_order_invoice',
        'weight',
        'shipping_cost_tax_excl',
        'shipping_cost_tax_incl',
        'tracking_number',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_order' => 'integer',
        'id_carrier' => 'integer',
        'id_order_invoice' => 'integer',
        'weight' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'id_carrier');
    }

    public function orderInvoice(): BelongsTo
    {
        return $this->belongsTo(OrderInvoice::class, 'id_order_invoice');
    }
}
