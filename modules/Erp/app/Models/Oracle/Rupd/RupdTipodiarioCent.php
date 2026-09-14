<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPODIARIO_CENT
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipodiarioCent extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipodiario_cent';

    protected $primaryKey = 'idtipodiario';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
