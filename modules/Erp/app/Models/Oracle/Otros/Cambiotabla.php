<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMBIOTABLA
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_CAMBIOTABLA_FCREACION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FCREACION
 *
 * ✅ IDX_CAMBIOTABLA_FILA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FILA
 *
 * ✅ IDX_CAMBIOTABLA_TRANSACCION (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: TRANSACCION
 *
 * PK_CAMBIOTABLA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMBIOTABLA
 */
class Cambiotabla extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cambiotabla';

    protected $primaryKey = 'idcambiotabla';

    public $timestamps = false;

    protected $fillable = [
        'tabla', 'fila', 'estado', 'tipo', 'transaccion',
        'idcontent_type',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cambiotabla
     * ✅ Usa PK_CAMBIOTABLA (indexado)
     */
    public function cambiotabla()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Cambiotabla::class, 'idcambiotabla', 'idcambiotabla');
    }
}
