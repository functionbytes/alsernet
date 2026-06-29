<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOCLI_MONTE2
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidocliMonte2 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidocli_monte2';

    protected $primaryKey = 'idlpedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
