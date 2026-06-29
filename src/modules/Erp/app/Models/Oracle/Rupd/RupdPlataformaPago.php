<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_PLATAFORMA_PAGO
 * Tabla de replicación/materialización de Oracle
 */
class RupdPlataformaPago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_plataforma_pago';

    protected $primaryKey = 'idplataforma_pago';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
