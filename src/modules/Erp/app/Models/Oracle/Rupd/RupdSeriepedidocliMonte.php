<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOCLI_MONTE
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidocliMonte extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidocli_monte';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
