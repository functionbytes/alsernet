<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ORDMFILTRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdOrdmfiltro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_ordmfiltro';

    protected $primaryKey = 'idordmfiltro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
