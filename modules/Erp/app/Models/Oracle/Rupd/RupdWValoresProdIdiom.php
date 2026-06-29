<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_VALORES_PROD_IDIOM
 * Tabla de replicación/materialización de Oracle
 */
class RupdWValoresProdIdiom extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_valores_prod_idiom';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
