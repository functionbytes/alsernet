<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_central';

    protected $primaryKey = 'iddeudacli_central';

    public $incrementing = false;

    public $timestamps = false;
}
