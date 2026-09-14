<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_AFILIADO
 * Tabla de replicación/materialización de Oracle
 */
class MlogAfiliado extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_afiliado';

    protected $primaryKey = 'idafiliado';

    public $incrementing = false;

    public $timestamps = false;
}
