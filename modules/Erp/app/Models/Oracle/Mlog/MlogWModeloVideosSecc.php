<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_W_MODELO_VIDEOS_SECC
 * Tabla de replicación/materialización de Oracle
 */
class MlogWModeloVideosSecc extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_w_modelo_videos_secc';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public $timestamps = false;
}
