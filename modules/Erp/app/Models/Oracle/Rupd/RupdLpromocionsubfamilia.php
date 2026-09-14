<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_LPROMOCIONSUBFAMILIA
 * Tabla de replicación/materialización de Oracle
 */
class RupdLpromocionsubfamilia extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_lpromocionsubfamilia';

    protected $primaryKey = 'idlpromocionsubfamiliaincluida';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
