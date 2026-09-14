<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPOTELEFONO
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipotelefono extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipotelefono';

    protected $primaryKey = 'idtipotelefono';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
