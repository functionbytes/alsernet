<?php

namespace Modules\Erp\Models\Oracle\Articulo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\ZonaPostal;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla ARTICULOZONAPOSTAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTICULOZONAPOSTAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTICULOZONAPOSTAL
 */
class Articulozonapostal extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'articulozonapostal';

    protected $primaryKey = 'idarticulozonapostal';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idarticulo', 'idzona_postal', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'permite_o_excluye',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Articulozonapostal
     * ✅ Usa PK_ARTICULOZONAPOSTAL (indexado)
     */
    public function articulozonapostal()
    {
        return $this->belongsTo(Articulozonapostal::class, 'idarticulozonapostal', 'idarticulozonapostal');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: ZonaPostal
     * ⚠️  SIN ÍNDICE en IDZONA_POSTAL
     */
    public function zonaPostal()
    {
        return $this->belongsTo(ZonaPostal::class, 'idzona_postal', 'idzona_postal');
    }
}
