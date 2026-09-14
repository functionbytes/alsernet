<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_MODELO
 * Tabla de replicación/materialización de Oracle
 */
class MlogWModelo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_modelo';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
