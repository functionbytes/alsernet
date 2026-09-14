<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Cobro\Formapago;
use Modules\Erp\Models\Oracle\Cobro\Tcobro;
use Modules\Erp\Models\Oracle\Otros\AsientoCent;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla COBROPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDCOBROPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCOBROPRO
 */
class Cobropro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cobropro';

    protected $primaryKey = 'idcobropro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'idformapago',
        'idcaja', 'idmovcaja', 'idtcobro', 'idasiento', 'fcobro',
        'importe', 'not', 'observaciones',
    ];

    protected $casts = [
        'fcobro' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cobropro
     * ✅ Usa PK_IDCOBROPRO (indexado)
     */
    public function cobropro()
    {
        return $this->belongsTo(Cobropro::class, 'idcobropro', 'idcobropro');
    }

    /**
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(Formapago::class, 'idformapago', 'idformapago');
    }

    /**
     * Relación: Tcobro
     * ⚠️  SIN ÍNDICE en IDTCOBRO
     */
    public function tcobro()
    {
        return $this->belongsTo(Tcobro::class, 'idtcobro', 'idtcobro');
    }

    /**
     * Relación: Asiento
     * ⚠️  SIN ÍNDICE en IDASIENTO
     */
    public function asiento()
    {
        return $this->belongsTo(AsientoCent::class, 'idasiento', 'idasiento');
    }
}
