<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LMFILTRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLmfiltro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lmfiltro';

    protected $primaryKey = 'idlmfiltro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
