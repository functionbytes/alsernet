<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\SupplyOrderState;

class SupplyOrderState extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order_state';

    protected $primaryKey = 'id_supply_order_state';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order_state',
        'name',
        'delivery_note',
        'editable',
        'receipt_state',
        'pending_receipt',
        'enclosed',
        'color',
    ];

    protected $casts = [
        'id_supply_order_state' => 'integer',
    ];

    public function supplyOrderState(): BelongsTo
    {
        return $this->belongsTo(SupplyOrderState::class, 'id_supply_order_state');
    }
}
