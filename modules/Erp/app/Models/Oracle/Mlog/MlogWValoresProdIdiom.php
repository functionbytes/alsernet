<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_VALORES_PROD_IDIOM
 * Tabla de replicación/materialización de Oracle
 */
class MlogWValoresProdIdiom extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_valores_prod_idiom';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
