<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_EJERCICIO_CONTABLE_C
 * Tabla de replicación/materialización de Oracle
 */
class MlogEjercicioContableC extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_ejercicio_contable_c';

    protected $primaryKey = 'idejercicio_contable';

    public $incrementing = false;

    public $timestamps = false;
}
