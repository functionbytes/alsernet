<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_COBROPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdCobropro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_cobropro';

    protected $primaryKey = 'idcobropro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
