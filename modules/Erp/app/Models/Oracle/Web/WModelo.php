<?php

namespace Modules\Erp\Models\Oracle\Web;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla W_MODELO
 *
 * ÍNDICES DISPONIBLES:
 * PK_W_MODELO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: ID
 */
class WModelo extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'w_modelo';

    protected $primaryKey = 'id';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'nombre', 'descripcion', 'imagen', 'activo', 'destacado',
        'descripcion_destacado', 'financiacion', 'precio_consultar', 'orden', 'destacar_nombre',
        'iva_portugal', 'papelaweb_multiple', 'tiene_productos_no_vendibles', 'texto_productos_no_vendibles', 'porcentaje_bono',
        'id_marca', 'venta_telefono', 'keywords', 'imagen_seo', 'estado',
        'idusuariocre', 'idusuariomod', 'idusuariobaja', 'precio_consultar_ficha', 'seo_title',
        'seo_metadescriptions',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con WAyudasMod
     */
    public function wAyudasMods()
    {
        return $this->hasMany(WAyudasMod::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WCaracteristicasOrden
     */
    public function wCaracteristicasOrdens()
    {
        return $this->hasMany(WCaracteristicasOrden::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Modelos relacionados (relación 1)
     */
    public function modelosRelacionadosRel1()
    {
        return $this->hasMany(WModelosRelacionados::class, 'rel1', 'idw_modelo');
    }

    /**
     * Modelos relacionados (relación 2)
     */
    public function modelosRelacionadosRel2()
    {
        return $this->hasMany(WModelosRelacionados::class, 'rel2', 'idw_modelo');
    }

    /**
     * Relación inversa con WModeloDocumentos
     */
    public function wModeloDocumentos()
    {
        return $this->hasMany(WModeloDocumentos::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WModeloIdioma
     */
    public function wModeloIdiomas()
    {
        return $this->hasMany(WModeloIdioma::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WModeloImagen
     */
    public function wModeloImagens()
    {
        return $this->hasMany(WModeloImagen::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WModeloVideos
     */
    public function wModeloVideos()
    {
        return $this->hasMany(WModeloVideos::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WPerfilesNav
     */
    public function wPerfilesNavs()
    {
        return $this->hasMany(WPerfilesNav::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WPerfilesProd
     */
    public function wPerfilesProds()
    {
        return $this->hasMany(WPerfilesProd::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WProducto
     */
    public function wProductos()
    {
        return $this->hasMany(WProducto::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación inversa con WProductosMasVendidos
     */
    public function wProductosMasVendidos()
    {
        return $this->hasMany(WProductosMasVendidos::class, 'id_modelo', 'idw_modelo');
    }

    /**
     * Relación: WAyudas
     * ✅ Usa PK_W_MODELO (indexado)
     */
    public function wAyudas()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Web\WAyudas::class, 'id', 'id');
    }
}
