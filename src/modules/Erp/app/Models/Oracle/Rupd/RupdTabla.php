<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TABLA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTabla extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tabla';

    protected $primaryKey = 'idtabla';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
