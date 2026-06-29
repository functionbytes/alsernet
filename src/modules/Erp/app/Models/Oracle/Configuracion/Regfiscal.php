<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\Subcuenta;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla REGFISCAL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_REGFISCAL_IDSUBCUENTA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSUBCUENTA
 *
 * PK_REGFISCAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDREGFISCAL
 */
class Regfiscal extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'regfiscal';

    protected $primaryKey = 'idregfiscal';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'idusuario', 'coniva', 'conrecargo', 'estado',
        'tipo', 'idsubcuenta',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Subcuenta
     */
    public function subcuenta()
    {
        return $this->belongsTo(Subcuenta::class, 'idsubcuenta', 'idsubcuenta_cent');
    }

    /**
     * Relación inversa con Pais
     */
    public function pais()
    {
        return $this->hasMany(Pais::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación inversa con Provincia
     */
    public function provincias()
    {
        return $this->hasMany(Provincia::class, 'idregfiscal', 'idregfiscal');
    }

    /**
     * Relación: Regfiscal
     * ✅ Usa PK_REGFISCAL (indexado)
     */
    public function regfiscal()
    {
        return $this->belongsTo(Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }
}
