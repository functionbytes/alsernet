<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_DEUDACLI_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogDeudacliDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_deudacli_ddleon';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;
}
