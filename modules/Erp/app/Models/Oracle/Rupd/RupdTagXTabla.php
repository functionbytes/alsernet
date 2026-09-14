<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_TAG_X_TABLA
 * Tabla de replicación/materialización de Oracle
 */
class RupdTagXTabla extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_tag_x_tabla';

    protected $primaryKey = 'idtag_x_tabla';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
