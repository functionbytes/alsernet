<?php

namespace Modules\Erp\Models\Oracle\Lote;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LOTE
 *
 * ÍNDICES DISPONIBLES:
 * PK_LOTE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLOTE
 */
class Lote extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lote';

    protected $primaryKey = 'idlote';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'precio', 'codlote', 'idimpuesto',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lote
     * ✅ Usa PK_LOTE (indexado)
     */
    public function lote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Lote::class, 'idlote', 'idlote');
    }

    /**
     * Relación: Impuesto
     * ⚠️  SIN ÍNDICE en IDIMPUESTO
     */
    public function impuesto()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Impuesto::class, 'idimpuesto', 'idimpuesto');
    }
}
