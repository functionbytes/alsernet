<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_invoice';

    protected $primaryKey = 'id_order_invoice';

    public $timestamps = false;

    protected $fillable = [
        'id_order',
        'number',
        'delivery_number',
        'total_discount_tax_excl',
        'total_discount_tax_incl',
        'total_paid_tax_excl',
        'total_paid_tax_incl',
        'total_products',
        'total_products_wt',
        'total_shipping_tax_excl',
        'total_shipping_tax_incl',
        'shipping_tax_computation_method',
        'total_wrapping_tax_excl',
        'total_wrapping_tax_incl',
        'shop_address',
        'note',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_order' => 'integer',
        'total_discount_tax_excl' => 'float',
        'total_discount_tax_incl' => 'float',
        'total_paid_tax_excl' => 'float',
        'total_paid_tax_incl' => 'float',
        'total_products' => 'float',
        'total_products_wt' => 'float',
        'total_shipping_tax_excl' => 'float',
        'total_shipping_tax_incl' => 'float',
        'total_wrapping_tax_excl' => 'float',
        'total_wrapping_tax_incl' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }
}
