<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Cobro\Formapago;
use Modules\Erp\Models\Oracle\Factura\Facturapro;
use Modules\Erp\Models\Oracle\Otros\Condicionpago;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DEUDAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_IDDEUDAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDAPRO
 */
class Deudapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'deudapro';

    protected $primaryKey = 'iddeudapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'borrado', 'idproveedor',
        'idformapago', 'idcondicionpago', 'idcaja', 'fdeuda', 'importe',
        'not', 'estado', 'observaciones',
    ];

    protected $casts = [
        'fdeuda' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Facturapro
     */
    public function facturapros()
    {
        return $this->hasMany(Facturapro::class, 'iddeudapro', 'iddeudapro');
    }

    /**
     * Relación inversa con Vencimientopro
     */
    public function vencimientopros()
    {
        return $this->hasMany(Vencimientopro::class, 'iddeudapro', 'iddeudapro');
    }

    /**
     * Relación: Deudapro
     * ✅ Usa PK_IDDEUDAPRO (indexado)
     */
    public function deudapro()
    {
        return $this->belongsTo(Deudapro::class, 'iddeudapro', 'iddeudapro');
    }

    /**
     * Relación: Proveedor
     * ⚠️  SIN ÍNDICE en IDPROVEEDOR
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'idproveedor', 'idproveedor');
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
     * Relación: Condicionpago
     * ⚠️  SIN ÍNDICE en IDCONDICIONPAGO
     */
    public function condicionpago()
    {
        return $this->belongsTo(Condicionpago::class, 'idcondicionpago', 'idcondicionpago');
    }
}
