<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_CLIENTECATALOGO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class MlogClientecatalogoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_clientecatalogo_cent';

    protected $primaryKey = 'idclientecatalogo';

    public $incrementing = false;

    public $timestamps = false;
}
