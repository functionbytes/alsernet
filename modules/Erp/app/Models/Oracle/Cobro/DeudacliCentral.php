<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla DEUDACLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_DEUDA_CENT_IDALBCLI_CENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI_CENTRAL
 *
 * ✅ IDX_DEUDA_CENT_IDALB_IDALMCENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI, IDALMACEN_CREACION
 *
 * ✅ IDX_DEUDA_CENT_IDCOBCLI_CENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCOBROCLI_CENTRAL
 *
 * ✅ INDX_DEUDACLI_CEN_IDALBARANCLI (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDALBARANCLI
 *
 * PK_DEUDACLI_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEUDACLI_CENTRAL
 */
class DeudacliCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'deudacli_central';

    protected $primaryKey = 'iddeudacli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'iddeudacli', 'idcobrocli', 'idcobrocli_central', 'idalbarancli', 'idalbarancli_central',
        'idformapago', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'importe', 'idalmacen_creacion',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: DeudacliCentral
     * ✅ Usa PK_DEUDACLI_CENTRAL (indexado)
     */
    public function deudacliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\DeudacliCentral::class, 'iddeudacli_central', 'iddeudacli_central');
    }

    /**
     * Relación: Deudacli
     * ⚠️  SIN ÍNDICE en IDDEUDACLI
     */
    public function deudacli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\DeudacliCapthaya::class, 'iddeudacli', 'iddeudacli');
    }

    /**
     * Relación: Cobrocli
     * ⚠️  SIN ÍNDICE en IDCOBROCLI
     */
    public function cobrocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\CobrocliCapthaya::class, 'idcobrocli', 'idcobrocli');
    }

    /**
     * Relación: CobrocliCentral
     * ✅ Usa IDX_DEUDA_CENT_IDCOBCLI_CENT (indexado)
     */
    public function cobrocliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\CobrocliCentral::class, 'idcobrocli_central', 'idcobrocli_central');
    }

    /**
     * Relación: Albarancli
     * ✅ Usa IDX_DEUDA_CENT_IDALB_IDALMCENT (indexado)
     */
    public function albarancli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCapthaya::class, 'idalbarancli', 'idalbarancli');
    }

    /**
     * Relación: AlbarancliCentral
     * ✅ Usa IDX_DEUDA_CENT_IDALBCLI_CENT (indexado)
     */
    public function albarancliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\AlbarancliCentral::class, 'idalbarancli_central', 'idalbarancli_central');
    }

    /**
     * Relación: Formapago
     * ⚠️  SIN ÍNDICE en IDFORMAPAGO
     */
    public function formapago()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\Formapago::class, 'idformapago', 'idformapago');
    }
}
