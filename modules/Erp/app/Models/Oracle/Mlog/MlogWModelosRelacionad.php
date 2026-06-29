<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_MODELOS_RELACIONAD
 * Tabla de replicación/materialización de Oracle
 */
class MlogWModelosRelacionad extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_modelos_relacionad';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
