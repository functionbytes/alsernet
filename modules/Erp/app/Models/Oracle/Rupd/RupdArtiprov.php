<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_ARTIPROV
 * Tabla de replicación/materialización de Oracle
 */
class RupdArtiprov extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_artiprov';

    protected $primaryKey = 'idartiprov';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
