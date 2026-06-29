<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_COBROCLI_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogCobrocliDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_cobrocli_ddleon';

    protected $primaryKey = 'idcobrocli';

    public $incrementing = false;

    public $timestamps = false;
}
