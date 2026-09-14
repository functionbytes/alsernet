<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_COBROCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogCobrocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cobrocli_central';

    protected $primaryKey = 'idcobrocli_central';

    public $incrementing = false;

    public $timestamps = false;
}
