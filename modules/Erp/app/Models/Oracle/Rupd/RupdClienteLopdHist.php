<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTE_LOPD_HIST
 * Tabla de replicación/materialización de Oracle
 */
class RupdClienteLopdHist extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cliente_lopd_hist';

    protected $primaryKey = 'idcliente_lopd_hist';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
