<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELO_DOCUMENTOS_SECCIONES
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_MODELO_DOC_SECC (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModeloDocumentosSecciones extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelo_documentos_secciones';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'titulo', 'idioma', 'orden', 'activo',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WModeloDocumentos
     */
    public function wModeloDocumentos()
    {
        return $this->hasMany(WModeloDocumentos::class, 'id_seccion', 'idw_modelo_documentos_secciones');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELO_DOC_SECC (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
