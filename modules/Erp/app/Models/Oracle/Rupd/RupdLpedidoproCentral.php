<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_CENTRAL
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproCentral extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_central';

    protected $primaryKey = 'idlpedidopro_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
