<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CATALOGO_IMPRESO
 * Tabla de replicación/materialización de Oracle
 */
class MlogCatalogoImpreso extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_catalogo_impreso';

    protected $primaryKey = 'idcatalogo_impreso';

    public $incrementing = false;

    public $timestamps = false;
}
