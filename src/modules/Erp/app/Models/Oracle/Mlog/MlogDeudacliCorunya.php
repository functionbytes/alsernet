<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_corunya';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;
}
