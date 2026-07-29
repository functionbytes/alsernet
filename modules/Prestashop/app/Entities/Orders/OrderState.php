<?php

namespace Modules\Prestashop\Entities\Orders;

use Illuminate\Database\Eloquent\Model;

class OrderState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_state';

    protected $primaryKey = 'id_order_state';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'template',
        'send_email',
        'module_name',
        'invoice',
        'color',
        'unremovable',
        'logable',
        'delivery',
        'hidden',
        'shipped',
        'paid',
        'pdf_invoice',
        'pdf_delivery',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
