<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CONTRASENA
 * Tabla de replicación/materialización de Oracle
 */
class MlogContrasena extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_contrasena';

    protected $primaryKey = 'idcontrasena';

    public $incrementing = false;

    public $timestamps = false;
}
