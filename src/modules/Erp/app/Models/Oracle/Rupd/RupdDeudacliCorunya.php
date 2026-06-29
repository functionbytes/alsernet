<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_DEUDACLI_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdDeudacliCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_deudacli_corunya';

    protected $primaryKey = 'iddeudacli';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
