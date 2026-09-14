<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LIQUIDACIONBONO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLiquidacionbono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_liquidacionbono';

    protected $primaryKey = 'idliquidacionbono';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
