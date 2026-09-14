<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TRASPASO_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdTraspasoTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_traspaso_tpvcor';

    protected $primaryKey = 'idtraspaso';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
