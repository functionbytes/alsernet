<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TESTSTOCK
 *
 * ÍNDICES DISPONIBLES:
 * PK_TESTSTOCK (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTESTSTOCK
 */
class Teststock extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'teststock';

    protected $primaryKey = 'idteststock';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'descripcion', 'tipo', 'mostrar_cent',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Teststock
     * ✅ Usa PK_TESTSTOCK (indexado)
     */
    public function teststock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Teststock::class, 'idteststock', 'idteststock');
    }
}
