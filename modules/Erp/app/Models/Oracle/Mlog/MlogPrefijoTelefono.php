<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PREFIJO_TELEFONO
 * Tabla de replicación/materialización de Oracle
 */
class MlogPrefijoTelefono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_prefijo_telefono';

    protected $primaryKey = 'idprefijo_telefono';

    public $incrementing = false;

    public $timestamps = false;
}
