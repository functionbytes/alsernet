<?php

namespace Modules\Erp\Models\Oracle\Albaran;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEALBARANCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEALBARANCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEALBARANCLI
 */
class Seriealbarancli extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriealbarancli_tpvcor';

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
}
