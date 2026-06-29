<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PAIS
 * Tabla de replicación/materialización de Oracle
 */
class RupdPais extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_pais';

    protected $primaryKey = 'idpais';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
