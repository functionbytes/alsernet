<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOPRO_DDLEO
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidoproDdleo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidopro_ddleo';

    protected $primaryKey = 'idseriepedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
