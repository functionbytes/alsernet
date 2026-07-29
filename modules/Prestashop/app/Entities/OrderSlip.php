<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\Order;

class OrderSlip extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_slip';

    protected $primaryKey = 'id_order_slip';

    public $timestamps = false;

    protected $fillable = [
        'id_order_slip',
        'id_customer',
        'id_order',
        'conversion_rate',
        'total_products_tax_excl',
        'total_products_tax_incl',
        'total_shipping_tax_excl',
        'total_shipping_tax_incl',
        'amount',
        'shipping_cost',
        'shipping_cost_amount',
        'partial',
        'date_add',
        'date_upd',
        'order_slip_type',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_order_slip' => 'integer',
        'id_customer' => 'integer',
        'id_order' => 'integer',
        'conversion_rate' => 'float',
        'total_products_tax_excl' => 'float',
        'total_products_tax_incl' => 'float',
        'total_shipping_tax_excl' => 'float',
        'total_shipping_tax_incl' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }
}
