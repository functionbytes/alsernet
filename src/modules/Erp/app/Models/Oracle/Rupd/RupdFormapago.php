<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_FORMAPAGO
 * Tabla de replicación/materialización de Oracle
 */
class RupdFormapago extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_formapago';

    protected $primaryKey = 'idformapago';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
