<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ORDMFILTRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogOrdmfiltro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_ordmfiltro';

    protected $primaryKey = 'idordmfiltro';

    public $incrementing = false;

    public $timestamps = false;
}
