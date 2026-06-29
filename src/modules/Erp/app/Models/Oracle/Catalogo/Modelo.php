<?php

namespace Modules\Erp\Models\Oracle\Catalogo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Configuracion\Marca;
use Modules\Erp\Models\Oracle\Otros\GrupoCl;
use Modules\Erp\Models\Oracle\Web\WCaracteristicasOrden;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla MODELO
 *
 * ÍNDICES DISPONIBLES:
 * PK_MODELO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDMODELO
 */
class Modelo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'modelo';

    protected $primaryKey = 'idmodelo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado', 'codigo',
        'idgrupo_cl', 'nombre', 'descripcion', 'estado_publicado_web',
        'idmarca', 'venta_telefono', 'precio_consultar_ficha',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'estado_publicado_web' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Marca
     */
    public function marca()
    {
        return $this->belongsTo(Marca::class, 'idmarca', 'idmarca');
    }

    /**
     * Relación: Modelo
     * ✅ Usa PK_MODELO (indexado)
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'idmodelo', 'idmodelo');
    }

    /**
     * Relación: GrupoCl
     * ⚠️  SIN ÍNDICE en IDGRUPO_CL
     */
    public function grupoCl()
    {
        return $this->belongsTo(GrupoCl::class, 'idgrupo_cl', 'idgrupo_cl');
    }

    /**
     * Artículos de este modelo
     */
    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'idmodelo', 'idmodelo');
    }

    /**
     * Características ordenadas del modelo (web)
     * ✅ Usa IDX_WCARACT_ORDEN_WMODELO (indexado en IDMODELO)
     */
    public function caracteristicasOrden()
    {
        return $this->hasMany(WCaracteristicasOrden::class, 'idmodelo', 'idmodelo')
            ->orderBy('orden');
    }
}
