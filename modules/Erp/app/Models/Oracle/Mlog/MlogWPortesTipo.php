<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_PORTES_TIPO
 * Tabla de replicación/materialización de Oracle
 */
class MlogWPortesTipo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_portes_tipo';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
