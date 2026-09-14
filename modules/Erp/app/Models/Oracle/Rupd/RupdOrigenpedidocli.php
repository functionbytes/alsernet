<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ORIGENPEDIDOCLI
 * Tabla de replicación/materialización de Oracle
 */
class RupdOrigenpedidocli extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_origenpedidocli';

    protected $primaryKey = 'idorigenpedidocli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
