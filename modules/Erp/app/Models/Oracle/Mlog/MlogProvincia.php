<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PROVINCIA
 * Tabla de replicación/materialización de Oracle
 */
class MlogProvincia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_provincia';

    protected $primaryKey = 'idprovincia';

    public $incrementing = false;

    public $timestamps = false;
}
