<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LCOBROPRO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LCOBROPRO_IDVENCIMIENTOPRO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDVENCIMIENTOPRO
 *
 * PK_IDLCOBROPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLCOBROPRO
 */
class Lcobropro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lcobropro';

    protected $primaryKey = 'idlcobropro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'idcobropro',
        'idvencimientopro', 'importe', 'not', 'observaciones',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Vencimientopro
     */
    public function vencimientopro()
    {
        return $this->belongsTo(Vencimientopro::class, 'idvencimientopro', 'idvencimientopro');
    }

    /**
     * Relación: Lcobropro
     * ✅ Usa PK_IDLCOBROPRO (indexado)
     */
    public function lcobropro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Lcobropro::class, 'idlcobropro', 'idlcobropro');
    }

    /**
     * Relación: Cobropro
     * ⚠️  SIN ÍNDICE en IDCOBROPRO
     */
    public function cobropro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Proveedor\Cobropro::class, 'idcobropro', 'idcobropro');
    }
}
