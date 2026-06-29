<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CLIENTETELEFONO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdClientetelefonoCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_clientetelefono_cent';

    protected $primaryKey = 'idclientetelefono';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
