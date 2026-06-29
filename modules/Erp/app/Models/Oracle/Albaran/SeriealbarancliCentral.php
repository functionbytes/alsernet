<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Almacen;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Models\Oracle\Serie\Serie;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEALBARANCLI_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEALBARANCLI_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEALBARANCLI_CENTRAL
 */
class SeriealbarancliCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriealbarancli_central';

    protected $primaryKey = 'idseriealbarancli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idseriealbarancli', 'estado', 'idusuariomod', 'prox_num', 'descripcion',
        'descripcioncorta', 'idcaja', 'idalmacen', 'idserie', 'idempresa',
        'fdesde', 'fhasta', 'idalmacen_creacion', 'rectificativa', 'pordefecto',
        'prox_num_fact_simpl', 'tipo',
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
     * Relación: SeriealbarancliCentral
     * ✅ Usa PK_SERIEALBARANCLI_CENTRAL (indexado)
     */
    public function seriealbarancliCentral()
    {
        return $this->belongsTo(SeriealbarancliCentral::class, 'idseriealbarancli_central', 'idseriealbarancli_central');
    }

    /**
     * Relación: Seriealbarancli
     * ⚠️  SIN ÍNDICE en IDSERIEALBARANCLI
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
