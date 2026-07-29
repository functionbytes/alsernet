<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_payment';

    protected $primaryKey = 'id_order_payment';

    public $timestamps = false;

    protected $fillable = [
        'id_order_payment',
        'order_reference',
        'id_currency',
        'amount',
        'payment_method',
        'conversion_rate',
        'transaction_id',
        'card_number',
        'card_brand',
        'card_expiration',
        'card_holder',
        'date_add',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'id_order_payment' => 'integer',
        'id_currency' => 'integer',
        'conversion_rate' => 'float',
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
