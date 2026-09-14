<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TMOTIVOANULACIONPEDI
 * Tabla de replicación/materialización de Oracle
 */
class MlogTmotivoanulacionpedi extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tmotivoanulacionpedi';

    protected $primaryKey = 'idtmotivoanulacionpedido';

    public $incrementing = false;

    public $timestamps = false;
}
