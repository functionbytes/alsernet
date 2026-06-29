<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPEDIDOPRO_CORUNYA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpedidoproCorunya extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpedidopro_corunya';

    protected $primaryKey = 'idlpedidopro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
