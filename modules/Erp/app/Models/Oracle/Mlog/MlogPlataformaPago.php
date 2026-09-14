<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_PLATAFORMA_PAGO
 * Tabla de replicación/materialización de Oracle
 */
class MlogPlataformaPago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_plataforma_pago';

    protected $primaryKey = 'idplataforma_pago';

    public $incrementing = false;

    public $timestamps = false;
}
