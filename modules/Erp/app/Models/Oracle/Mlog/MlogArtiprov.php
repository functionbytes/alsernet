<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_ARTIPROV
 * Tabla de replicación/materialización de Oracle
 */
class MlogArtiprov extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_artiprov';

    protected $primaryKey = 'idartiprov';

    public $incrementing = false;

    public $timestamps = false;
}
