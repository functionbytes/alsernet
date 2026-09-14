<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_SERIEFACTURAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogSeriefacturapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_seriefacturapro';

    protected $primaryKey = 'idseriefacturapro';

    public $incrementing = false;

    public $timestamps = false;
}
