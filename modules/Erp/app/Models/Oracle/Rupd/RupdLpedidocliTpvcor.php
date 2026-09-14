<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOCLI_TPVCOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidocliTpvcor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidocli_tpvcor';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
