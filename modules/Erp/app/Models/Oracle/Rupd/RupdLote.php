<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LOTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdLote extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lote';

    protected $primaryKey = 'idlote';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
