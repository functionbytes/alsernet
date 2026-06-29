<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LIMPORTACION_ARTICUL2
 * Tabla de replicación/materialización de Oracle
 */
class RupdLimportacionArticul2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_limportacion_articul2';

    protected $primaryKey = 'idlimportacion_articulo_ext';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
