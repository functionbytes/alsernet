<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DEUDACLI_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * ✅ INDX_DEUDACLI_MON_IDALBARANCLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI
 *
 * PK_DEUDACLI_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDACLI
 */
class DeudacliMonte2 extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'deudacli_monte2';

    protected $primaryKey = 'iddeudacli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcobrocli', 'idalbarancli', 'idformapago', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'estado', 'importe',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Deudacli
     * ✅ Usa PK_DEUDACLI_MONTE2 (indexado)
     */
    public function deudacli()
    {
        return $this->belongsTo(DeudacliCapthaya::class, 'iddeudacli', 'iddeudacli');
    }

    /**
     * Relación: Cobrocli
     * ⚠️  SIN ÍNDICE en IDCOBROCLI
     */
    public function cobrocli()
    {
        return $this->belongsTo(CobrocliCapthaya::class, 'idcobrocli', 'idcobrocli');
    }

    /**
     * Relación: Albarancli
     * ✅ Usa INDX_DEUDACLI_MON_IDALBARANCLI (indexado)
     */
    public function albarancli()
    {
        return $this->belongsTo(AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(Formapago::class, 'idformapago', 'idformapago');
    }
}
