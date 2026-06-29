<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_CATALOGOIDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class RupdCatalogoidioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_catalogoidioma';

    protected $primaryKey = 'idcatalogoidioma';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
