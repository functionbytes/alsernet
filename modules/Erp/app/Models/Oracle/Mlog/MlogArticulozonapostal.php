<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTICULOZONAPOSTAL
 * Tabla de replicación/materialización de Oracle
 */
class MlogArticulozonapostal extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_articulozonapostal';

    protected $primaryKey = 'idarticulozonapostal';

    public $incrementing = false;

    public $timestamps = false;
}
