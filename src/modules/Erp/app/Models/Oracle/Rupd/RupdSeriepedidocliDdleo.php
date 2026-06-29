<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_SERIEPEDIDOCLI_DDLEO
 * Tabla de replicación/materialización de Oracle
 */
class RupdSeriepedidocliDdleo extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_seriepedidocli_ddleo';

    protected $primaryKey = 'idseriepedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
