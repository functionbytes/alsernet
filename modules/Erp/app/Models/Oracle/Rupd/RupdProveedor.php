<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PROVEEDOR
 * Tabla de replicación/materialización de Oracle
 */
class RupdProveedor extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_proveedor';

    protected $primaryKey = 'idproveedor';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
