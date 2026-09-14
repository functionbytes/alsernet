<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_CARACTERISTICAS_PROD
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_CARACTERISTICAS_PROD (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WCaracteristicasProd extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_caracteristicas_prod';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaja',
        'orden',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WCaracteristicasOrden
     */
    public function wCaracteristicasOrdens()
    {
        return $this->hasMany(WCaracteristicasOrden::class, 'id_caracteristica', 'idw_caracteristicas_prod');
    }

    /**
     * Relación inversa con WCaracteristicasProdIdioma
     */
    public function wCaracteristicasProdIdiomas()
    {
        return $this->hasMany(WCaracteristicasProdIdioma::class, 'id_caracteristica', 'idw_caracteristicas_prod');
    }

    /**
     * Relación inversa con WValoresProd
     */
    public function wValoresProds()
    {
        return $this->hasMany(WValoresProd::class, 'id_caracteristica', 'idw_caracteristicas_prod');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_CARACTERISTICAS_PROD (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(WAyudas::class, 'id', 'id');
    }
}
