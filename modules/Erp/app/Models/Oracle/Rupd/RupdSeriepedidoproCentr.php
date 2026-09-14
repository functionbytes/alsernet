<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOPRO_CENTR
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidoproCentr extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidopro_centr';

    protected $primaryKey = 'idseriepedidopro_central';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
