<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_OBJETO
 * Tabla de replicación/materialización de Oracle
 */
class RupdObjeto extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_objeto';

    protected $primaryKey = 'idobjeto';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
