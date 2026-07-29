<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCartRule extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_cart_rule';

    protected $primaryKey = 'id_order_cart_rule';

    public $timestamps = false;

    protected $fillable = [
        'id_order',
        'id_cart_rule',
        'id_order_invoice',
        'name',
        'value',
        'value_tax_excl',
        'free_shipping',
    ];

    protected $casts = [
        'id_order' => 'integer',
        'id_cart_rule' => 'integer',
        'id_order_invoice' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function orderInvoice(): BelongsTo
    {
        return $this->belongsTo(OrderInvoice::class, 'id_order_invoice');
    }
}
