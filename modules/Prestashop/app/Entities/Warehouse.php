<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Prestashop\Entities\Stock\Warehouse;

class Warehouse extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_warehouse';

    protected $primaryKey = 'id_warehouse';

    public $timestamps = false;

    protected $fillable = [
        'id_warehouse',
        'id_address',
        'reference',
        'name',
        'id_employee',
        'id_currency',
        'deleted',
        'management_type',
    ];

    protected $casts = [
        'deleted' => 'boolean',
        'id_warehouse' => 'integer',
        'id_address' => 'integer',
        'id_employee' => 'integer',
        'id_currency' => 'integer',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_employee');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'id_currency');
    }
}
