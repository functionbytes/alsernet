<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_W_MODELO_VIDEOS_SECC
 * Tabla de replicación/materialización de Oracle
 */
class RupdWModeloVideosSecc extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_w_modelo_videos_secc';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
