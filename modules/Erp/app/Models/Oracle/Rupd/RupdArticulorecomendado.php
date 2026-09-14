<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTICULORECOMENDADO
 * Tabla de replicación/materialización de Oracle
 */
class RupdArticulorecomendado extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_articulorecomendado';

    protected $primaryKey = 'idarticulorecomendado';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
