<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELO_VIDEOS_SECCIONES
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_MODELO_VIDEOS_SECC (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModeloVideosSecciones extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelo_videos_secciones';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'titulo', 'idioma', 'orden', 'activo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WModeloVideos
     */
    public function wModeloVideos()
    {
        return $this->hasMany(WModeloVideos::class, 'id_seccion', 'idw_modelo_videos_secciones');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELO_VIDEOS_SECC (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
