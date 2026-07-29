<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\SupplyOrder;
use Modules\Prestashop\Entities\Stock\SupplyOrderState;
use Modules\Prestashop\Entities\Stock\Warehouse;

class SupplyOrder extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_supply_order';

    protected $primaryKey = 'id_supply_order';

    public $timestamps = false;

    protected $fillable = [
        'id_supply_order',
        'id_supplier',
        'supplier_name',
        'id_lang',
        'id_warehouse',
        'id_supply_order_state',
        'id_currency',
        'id_ref_currency',
        'reference',
        'date_add',
        'date_upd',
        'date_delivery_expected',
        'total_te',
        'total_with_discount_te',
        'total_ti',
        'total_tax',
        'discount_rate',
        'discount_value_te',
        'is_template',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'is_template' => 'boolean',
        'id_supply_order' => 'integer',
        'id_supplier' => 'integer',
        'id_lang' => 'integer',
        'id_warehouse' => 'integer',
        'id_supply_order_state' => 'integer',
        'id_currency' => 'integer',
        'id_ref_currency' => 'integer',
        'total_te' => 'float',
        'total_with_discount_te' => 'float',
        'total_ti' => 'float',
        'total_tax' => 'float',
        'discount_rate' => 'float',
    ];

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'id_supply_order');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function lang(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'id_lang');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }

    public function supplyOrderState(): BelongsTo
    {
        return $this->belongsTo(SupplyOrderState::class, 'id_supply_order_state');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
