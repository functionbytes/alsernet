<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULORECOMENDADO
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticulorecomendado extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulorecomendado';

    protected $primaryKey = 'idarticulorecomendado';

    public $incrementing = false;

    public $timestamps = false;
}
