<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Orders\Order;
use Modules\Prestashop\Entities\Stock\StockMvtReason;
use Modules\Prestashop\Entities\Stock\SupplyOrder;

class StockMvt extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_stock_mvt';

    protected $primaryKey = 'id_stock_mvt';

    public $timestamps = false;

    protected $fillable = [
        'id_stock_mvt',
        'date_add',
        'id_employee',
        'employee_firstname',
        'employee_lastname',
        'id_stock',
        'physical_quantity',
        'id_stock_mvt_reason',
        'id_order',
        'sign',
        'id_supply_order',
        'last_wa',
        'current_wa',
        'price_te',
        'referer',
        'date_upd',
        'quantity',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
        'id_stock_mvt' => 'integer',
        'id_employee' => 'integer',
        'id_stock' => 'integer',
        'id_stock_mvt_reason' => 'integer',
        'id_order' => 'integer',
        'id_supply_order' => 'integer',
        'quantity' => 'integer',
        'price_te' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function stockMvtReason(): BelongsTo
    {
        return $this->belongsTo(StockMvtReason::class, 'id_stock_mvt_reason');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    public function supplyOrder(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'id_supply_order');
    }
}
