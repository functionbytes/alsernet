<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONEXCLUIDO
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromocionexcluido extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromocionexcluido';

    protected $primaryKey = 'idlpromocionexcluido';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
