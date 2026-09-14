<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOCLI_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidocliCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidocli_central';

    protected $primaryKey = 'idlpedidocli_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
