<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELO_DOCUMENTOS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_MODELO_DOCUMENTOS_ID_MOD (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_MODELO
 *
 * ✅ IDX_W_MODELO_DOCUMENTOS_ID_SEC (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_SECCION
 *
 * PK_W_MODELO_DOC (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModeloDocumentos extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelo_documentos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'titulo', 'contenido', 'origen_externo', 'activo', 'orden',
        'idioma', 'id_modelo', 'id_seccion', 'idmodelo',
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
     * Relación con WModeloDocumentosSecciones
     */
    public function _seccion()
    {
        return $this->belongsTo(WModeloDocumentosSecciones::class, 'id_seccion', 'idw_modelo_documentos_secciones');
    }

    /**
     * Relación: Modelo
     * ✅ Usa IDX_W_MODELO_DOCUMENTOS_ID_MOD (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModelo::class, 'id_modelo', 'id');
    }

    /**
     * Relación: Seccion
     * ✅ Usa IDX_W_MODELO_DOCUMENTOS_ID_SEC (indexado)
     */
    public function seccion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WModeloDocumentosSecciones::class, 'id_seccion', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELO_DOC (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
