<?php

namespace Modules\Erp\Models\Oracle\Serie;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIE_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_SERIE_CENTRAL_IDSERIEGENER (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEGENERICA_GRUPOCONTA
 *
 * PK_SERIE_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIE
 */
class SerieCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'serie_central';

    protected $primaryKey = 'idserie';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idempresa', 'descripcioncorta', 'tipo', 'n_factura', 'estado',
        'idusuariomod', 'nseriecontaplus', 'anno', 'idtipodiario', 'fdesde',
        'fhasta', 'idalmacen', 'idseriegenerica_grupoconta', 'rectificativa', 'tfactura',
        'pordefecto', 'simplificada',
    ];

    protected $casts = [
        'fdesde' => 'datetime',
        'fhasta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Seriegenerica
     */
    public function seriegenerica_grupoconta()
    {
        return $this->belongsTo(Seriegenerica::class, 'idseriegenerica_grupoconta', 'idseriegenerica_grupoconta');
    }
}
