<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla BANCO
 *
 * ÍNDICES DISPONIBLES:
 * PK_BANCO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDBANCO
 */
class Banco extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'banco';

    protected $primaryKey = 'idbanco';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'descripcion', 'codigo', 'idusuariomod', 'idsubcuenta',
        'bic',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Banco
     * ✅ Usa PK_BANCO (indexado)
     */
    public function banco()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Banco::class, 'idbanco', 'idbanco');
    }

    /**
     * Relación: Subcuenta
     * ⚠️  SIN ÍNDICE en IDSUBCUENTA
     */
    public function subcuenta()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Subcuenta::class, 'idsubcuenta', 'idsubcuenta');
    }
}
