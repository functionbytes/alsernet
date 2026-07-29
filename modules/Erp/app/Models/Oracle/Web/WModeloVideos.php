<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELO_VIDEOS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_MODELO_VIDEOS_ID_MODELO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_W_MODELO_VIDEOS_ID_SECCION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_SECCION
 *
 * PK_W_MODELO_VIDEOS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModeloVideos extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelo_videos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'titulo', 'contenido', 'origen_externo', 'visible_ficha', 'activo',
        'orden', 'idioma', 'id_modelo', 'id_seccion', 'idmodelo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WModelo
     */
    public function _modelo()
    {
        return $this->belongsTo(WModelo::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación con WModeloVideosSecciones
     */
    public function _seccion()
    {
        return $this->belongsTo(WModeloVideosSecciones::class, 'id_seccion', 'idw_modelo_videos_secciones');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_W_MODELO_VIDEOS_ID_MODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: Seccion
     * ✅ Usa IDX_W_MODELO_VIDEOS_ID_SECCION (indexado)
     */
    public function seccion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModeloVideosSecciones::class, 'id_seccion', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELO_VIDEOS (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
