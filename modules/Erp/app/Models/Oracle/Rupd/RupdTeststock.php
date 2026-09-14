<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TESTSTOCK
 * Tabla de replicación/materialización de Oracle
 */
class RupdTeststock extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_teststock';

    protected $primaryKey = 'idteststock';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
