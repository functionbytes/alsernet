<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_DESCUENTOS_RELACIO1
 * Tabla de replicación/materialización de Oracle
 */
class RupdWDescuentosRelacio1 extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_descuentos_relacio1';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
