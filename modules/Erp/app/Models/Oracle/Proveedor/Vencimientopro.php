<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla VENCIMIENTOPRO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_VENCIMIENTOPRO_IDDEUDAPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDAPRO
 *
 * PK_IDVENCIMIENTOPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDVENCIMIENTOPRO
 */
class Vencimientopro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'vencimientopro';

    protected $primaryKey = 'idvencimientopro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'iddeudapro',
        'fvencimiento', 'importe', 'not', 'estado', 'observaciones',
        'exportado',
    ];

    protected $casts = [
        'fvencimiento' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Deudapro
     */
    public function deudapro()
    {
        return $this->belongsTo(Deudapro::class, 'iddeudapro', 'iddeudapro');
    }

    /**
     * Relación inversa con Lcobropro
     */
    public function lcobropros()
    {
        return $this->hasMany(Lcobropro::class, 'idvencimientopro', 'idvencimientopro');
    }

    /**
     * Relación: Vencimientopro
     * ✅ Usa PK_IDVENCIMIENTOPRO (indexado)
     */
    public function vencimientopro()
    {
        return $this->belongsTo(Vencimientopro::class, 'idvencimientopro', 'idvencimientopro');
    }
}
