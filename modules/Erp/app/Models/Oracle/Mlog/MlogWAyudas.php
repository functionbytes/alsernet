<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_AYUDAS
 * Tabla de replicación/materialización de Oracle
 */
class MlogWAyudas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_ayudas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
