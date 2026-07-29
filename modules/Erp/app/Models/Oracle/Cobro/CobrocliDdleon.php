<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla COBROCLI_DDLEON
 *
 * ÍNDICES DISPONIBLES:
 * PK_COBROCLI_DDLEON (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCOBROCLI
 */
class CobrocliDdleon extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cobrocli_ddleon';

    protected $primaryKey = 'idcobrocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'importe_cobrado',
        'not', 'importe_libre', 'not', 'fcobro', 'idformapago',
        'idtransportista', 'idvale', 'idcaja', 'idmovcaja', 'idcliente',
        'idasiento', 'segundamano',
    ];

    protected $casts = [
        'fcobro' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Cobrocli
     * ✅ Usa PK_COBROCLI_DDLEON (indexado)
     */
    public function cobrocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\CobrocliCapthaya::class, 'idcobrocli', 'idcobrocli');
    }

    /**
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\Formapago::class, 'idformapago', 'idformapago');
    }

    /**
     * Relación: Transportista
     * ⚠️  SIN ÍNDICE en IDTRANSPORTISTA
     */
    public function transportista()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Transportista::class, 'idtransportista', 'idtransportista');
    }

    /**
     * Relación: Vale
     * ⚠️  SIN ÍNDICE en IDVALE
     */
    public function vale()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Vale::class, 'idvale', 'idvale');
    }

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cliente\Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Asiento
     * ⚠️  SIN ÍNDICE en IDASIENTO
     */
    public function asiento()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\AsientoCent::class, 'idasiento', 'idasiento');
    }
}
