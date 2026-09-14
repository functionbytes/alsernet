<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TESTFACTURA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTestfactura extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_testfactura';

    protected $primaryKey = 'idtestfactura';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
