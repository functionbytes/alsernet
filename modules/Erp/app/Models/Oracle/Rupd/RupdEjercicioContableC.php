<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_EJERCICIO_CONTABLE_C
 * Tabla de replicación/materialización de Oracle
 */
class RupdEjercicioContableC extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_ejercicio_contable_c';

    protected $primaryKey = 'idejercicio_contable';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
