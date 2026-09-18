<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Models\Oracle\Serie\Serie;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEALBARANCLI_CAPTHAYA
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEALBARANCLI_CAPTHAYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEALBARANCLI
 */
class SeriealbarancliCapthaya extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriealbarancli_capthaya';

    protected $primaryKey = 'idseriealbarancli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariomod', 'prox_num', 'descripcion', 'descripcioncorta',
        'idcaja', 'idalmacen', 'idserie', 'idempresa', 'fdesde',
        'fhasta', 'rectificativa', 'pordefecto', 'prox_num_fact_simpl', 'tipo',
    ];

    protected $casts = [
        'fdesde' => 'datetime',
        'fhasta' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Seriealbarancli
     * ✅ Usa PK_SERIEALBARANCLI_CAPTHAYA (indexado)
     */
    public function seriealbarancli()
    {
        return $this->belongsTo(SeriealbarancliCapthaya::class, 'idseriealbarancli', 'idseriealbarancli');
    }

    /**
     * Relación: Almacen
     * ⚠️  SIN ÍNDICE en IDALMACEN
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Serie
     * ⚠️  SIN ÍNDICE en IDSERIE
     */
    public function serie()
    {
        return $this->belongsTo(Serie::class, 'idserie', 'idserie');
    }

    /**
     * Relación: Empresa
     * ⚠️  SIN ÍNDICE en IDEMPRESA
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idempresa', 'idempresa');
    }
}
