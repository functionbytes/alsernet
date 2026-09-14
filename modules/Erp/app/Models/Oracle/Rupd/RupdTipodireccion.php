<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TIPODIRECCION
 * Tabla de replicación/materialización de Oracle
 */
class RupdTipodireccion extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tipodireccion';

    protected $primaryKey = 'idtipodireccion';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
