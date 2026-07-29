<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\OrderState;

class OrderState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_order_state';

    protected $primaryKey = 'id_order_state';

    public $timestamps = false;

    protected $fillable = [
        'id_order_state',
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
        'deleted',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'id_order_state' => 'integer',
    ];

    public function orderState(): BelongsTo
    {
        return $this->belongsTo(OrderState::class, 'id_order_state');
    }
}
