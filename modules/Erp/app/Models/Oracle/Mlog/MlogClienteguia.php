<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTEGUIA
 * Tabla de replicación/materialización de Oracle
 */
class MlogClienteguia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clienteguia';

    protected $primaryKey = 'idclienteguia';

    public $incrementing = false;

    public $timestamps = false;
}
