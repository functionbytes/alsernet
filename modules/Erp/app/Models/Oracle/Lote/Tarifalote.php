<?php

namespace Modules\Erp\Models\Oracle\Lote;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Regpais;
use Modules\Erp\Models\Oracle\Otros\Ttarifa;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TARIFALOTE
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_TARIFA_LOTE_REGPAIS (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREGPAIS
 *
 * PK_TARIFALOTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTARIFALOTE
 *
 * ⚠️  UK_TARIFA_LOTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLLOTE, IDREGPAIS, IDTTARIFA, ESTADO
 */
class Tarifalote extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tarifalote';

    protected $primaryKey = 'idtarifalote';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idllote', 'estado', 'idusuariomod', 'precio', 'not',
        'idregpais', 'idttarifa',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Regpais
     */
    public function regpais()
    {
        return $this->belongsTo(Regpais::class, 'idregpais', 'idregpais');
    }

    /**
     * Relación con Ttarifa
     */
    public function ttarifa()
    {
        return $this->belongsTo(Ttarifa::class, 'idttarifa', 'idttarifa');
    }

    /**
     * Relación: Tarifalote
     * ✅ Usa PK_TARIFALOTE (indexado)
     */
    public function tarifalote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Tarifalote::class, 'idtarifalote', 'idtarifalote');
    }

    /**
     * Relación: Llote
     * ✅ Usa UK_TARIFA_LOTE (indexado)
     */
    public function llote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Llote::class, 'idllote', 'idllote');
    }
}
