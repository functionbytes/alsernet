<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_DESCUENTOS_RELACIO
 * Tabla de replicación/materialización de Oracle
 */
class MlogWDescuentosRelacio extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_descuentos_relacio';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
