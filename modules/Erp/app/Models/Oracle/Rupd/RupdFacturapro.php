<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FACTURAPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdFacturapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_facturapro';

    protected $primaryKey = 'idfacturapro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
