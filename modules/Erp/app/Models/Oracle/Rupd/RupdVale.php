<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_VALE
 * Tabla de replicación/materialización de Oracle
 */
class RupdVale extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_vale';

    protected $primaryKey = 'idvale';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
