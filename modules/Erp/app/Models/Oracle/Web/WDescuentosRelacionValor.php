<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_DESCUENTOS_RELACION_VALOR
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_W_DESCUENTOS_RELACION_VALO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID_VALOR
 *
 * PK_W_DTO_RELACION_VALOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WDescuentosRelacionValor extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_descuentos_relacion_valor';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'id_descuento_relacionado', 'id_valor',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con WDescuentosRelacionados
     */
    public function _descuento_relacionado()
    {
        return $this->belongsTo(WDescuentosRelacionados::class, 'id_descuento_relacionado', 'idw_descuentos_relacionados');
    }

    /**
     * Relación con WValoresNav
     */
    public function _valor()
    {
        return $this->belongsTo(WValoresNav::class, 'id_valor', 'idw_valores_nav');
    }

    /**
     * Relación: DescuentoRelacionado
     * ⚠️  SIN ÍNDICE en ID_DESCUENTO_RELACIONADO
     */
    public function descuentoRelacionado()
    {
        return $this->belongsTo(WDescuentosRelacionados::class, 'id_descuento_relacionado', 'id');
    }

    /**
     * Relación: Valor
     * ✅ Usa IDX_W_DESCUENTOS_RELACION_VALO (indexado)
     */
    public function valor()
    {
        return $this->belongsTo(WValoresNav::class, 'id_valor', 'id');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_DTO_RELACION_VALOR (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
