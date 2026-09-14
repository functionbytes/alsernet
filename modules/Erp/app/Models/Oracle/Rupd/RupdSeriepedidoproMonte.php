<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOPRO_MONTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidoproMonte extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidopro_monte';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
