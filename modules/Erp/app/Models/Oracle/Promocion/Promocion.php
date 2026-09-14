<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PROMOCION
 *
 * ÍNDICES DISPONIBLES:
 * PK_PROMOCION (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPROMOCION
 */
class Promocion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'promocion';

    protected $primaryKey = 'idpromocion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcatalogo', 'nombre', 'faplicacion_desde', 'faplicacion_hasta', 'estado',
        'fvalidez_desde', 'idusuariocre', 'idusuariomod', 'idusuariobaja', 'imprimebonos',
        'leyenda', 'ventas_minimas', 'venta_acumulada', 'dias_validez', 'idcatalogo_consumo',
        'email_enviar', 'idtplantilla_email',
    ];

    protected $casts = [
        'faplicacion_desde' => 'datetime',
        'faplicacion_hasta' => 'datetime',
        'fvalidez_desde' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Catalogo
     */
    public function catalogo_consumo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo_consumo', 'idcatalogo');
    }

    /**
     * Relación inversa con Lpromocionorigenexcluido
     */
    public function lpromocionorigenexcluidos()
    {
        return $this->hasMany(Lpromocionorigenexcluido::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación inversa con Lpromocionsubfamiliaincluida
     */
    public function lpromocionsubfamiliaincluidas()
    {
        return $this->hasMany(Lpromocionsubfamiliaincluida::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación inversa con Lpromociontagincluido
     */
    public function lpromociontagincluidos()
    {
        return $this->hasMany(Lpromociontagincluido::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: CatalogoConsumo
     * ⚠️  SIN ÍNDICE en IDCATALOGO_CONSUMO
     */
    public function catalogoConsumo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo_consumo', 'idcatalogo');
    }

    /**
     * Relación: Promocion
     * ✅ Usa PK_PROMOCION (indexado)
     */
    public function promocion()
    {
        return $this->belongsTo(Promocion::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
    }
}
