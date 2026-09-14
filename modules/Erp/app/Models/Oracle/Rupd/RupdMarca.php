<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_MARCA
 * Tabla de replicación/materialización de Oracle
 */
class RupdMarca extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_marca';

    protected $primaryKey = 'idmarca';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
