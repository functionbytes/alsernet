<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\Ttarifa;
use Modules\Erp\Models\Oracle\Proveedor\Proveedor;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PAIS
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_PAIS_IDREGFISCAL (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREGFISCAL
 *
 * PK_PAIS (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPAIS
 *
 * ⚠️  UK_PAIS_CODIGOISO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: CODIGOISO
 *
 * ⚠️  UK_PAIS_DESCRIPCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: DESCRIPCION
 */
class Pais extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'pais';

    protected $primaryKey = 'idpaisantiguo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpais', 'descripcion', 'codigoiso', 'ididioma', 'idmoneda',
        'estado', 'idmonedaantiguo', 'ididiomaantiguo', 'comunitario', 're_validacion_cp',
        'codigo_chronoexpress', 'idregfiscal', 'idusuariomod', 'idttarifa',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Regfiscal
     */
    public function regfiscal()
    {
        return $this->belongsTo(Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación con Ttarifa
     */
    public function ttarifa()
    {
        return $this->belongsTo(Ttarifa::class, 'idttarifa', 'idttarifa');
    }

    /**
     * Relación inversa con Proveedor
     */
    public function proveedors()
    {
        return $this->hasMany(Proveedor::class, 'idpais', 'idpais');
    }

    /**
     * Relación: Pais
     * ✅ Usa PK_PAIS (indexado)
     */
    public function pais()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Pais::class, 'idpais', 'idpais');
    }

    /**
     * Relación: Idioma
     * ⚠️  SIN ÍNDICE en IDIDIOMA
     */
    public function idioma()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Idioma::class, 'ididioma', 'ididioma');
    }

    /**
     * Relación: Moneda
     * ⚠️  SIN ÍNDICE en IDMONEDA
     */
    public function moneda()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Moneda::class, 'idmoneda', 'idmoneda');
    }
}
