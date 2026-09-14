<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CAMPO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCampo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_campo';

    protected $primaryKey = 'idcampo';

    public $incrementing = false;

    public $timestamps = false;
}
