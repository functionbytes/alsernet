<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTIPROV_TARIFAPRO
 * Tabla de replicación/materialización de Oracle
 */
class MlogArtiprovTarifapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_artiprov_tarifapro';

    protected $primaryKey = 'idartiprov_tarifapro';

    public $incrementing = false;

    public $timestamps = false;
}
