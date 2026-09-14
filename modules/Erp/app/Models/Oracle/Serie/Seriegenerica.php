<?php

namespace Modules\Erp\Models\Oracle\Serie;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEGENERICA
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEGENERICA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEGENERICA
 */
class Seriegenerica extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriegenerica';

    protected $primaryKey = 'idseriegenerica';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'proxnumero',
        'codigo', 'descripcion', 'fdesde', 'fhasta', 'tipo',
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
     * Relación inversa con Serie
     */
    public function series()
    {
        return $this->hasMany(Serie::class, 'idseriegenerica_grupoconta', 'idseriegenerica');
    }

    /**
     * Relación: Seriegenerica
     * ✅ Usa PK_SERIEGENERICA (indexado)
     */
    public function seriegenerica()
    {
        return $this->belongsTo(Seriegenerica::class, 'idseriegenerica', 'idseriegenerica');
    }
}
