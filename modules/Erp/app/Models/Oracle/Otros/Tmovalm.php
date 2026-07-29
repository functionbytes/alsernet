<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TMOVALM
 *
 * ÍNDICES DISPONIBLES:
 * PK_TMOVALM (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTMOVALM
 */
class Tmovalm extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'tmovalm';

    protected $primaryKey = 'idtmovalm';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idteststock', 'tes_idteststock', 'estado', 'descripcion', 'oporigen',
        'opdestino', 'parausuario',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Tmovalm
     * ✅ Usa PK_TMOVALM (indexado)
     */
    public function tmovalm()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Tmovalm::class, 'idtmovalm', 'idtmovalm');
    }

    /**
     * Relación: Teststock
     * ⚠️  SIN ÍNDICE en IDTESTSTOCK
     */
    public function teststock()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Teststock::class, 'idteststock', 'idteststock');
    }
}
