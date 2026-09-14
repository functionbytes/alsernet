<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_TIENDAS_IDIOMAS
 * Tabla de replicación/materialización de Oracle
 */
class RupdWTiendasIdiomas extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_tiendas_idiomas';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
