<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_TAMANO_TIPOFOTOGRAFI
 * Tabla de replicación/materialización de Oracle
 */
class MlogTamanoTipofotografi extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_tamano_tipofotografi';

    protected $primaryKey = 'idtamano_tipofotografia';

    public $incrementing = false;

    public $timestamps = false;
}
