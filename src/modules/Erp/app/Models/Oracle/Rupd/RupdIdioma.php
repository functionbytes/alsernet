<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_IDIOMA
 * Tabla de replicación/materialización de Oracle
 */
class RupdIdioma extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_idioma';

    protected $primaryKey = 'ididioma';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
