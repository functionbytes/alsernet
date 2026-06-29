<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TRASPASO_DDLEON
 * Tabla de replicación/materialización de Oracle
 */
class MlogTraspasoDdleon extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_traspaso_ddleon';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;
}
