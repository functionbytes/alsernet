<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_IMPUESTO
 * Tabla de replicación/materialización de Oracle
 */
class RupdImpuesto extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_impuesto';

    protected $primaryKey = 'idimpuesto';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
