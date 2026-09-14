<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTIPROV_TARIFAPRO
 * Tabla de replicación/materialización de Oracle
 */
class RupdArtiprovTarifapro extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_artiprov_tarifapro';

    protected $primaryKey = 'idartiprov_tarifapro';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
