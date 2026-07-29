<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOCLI_CENTRAL (Líneas de Pedido)
 *
 * @property int $idlpedidocli_central Clave primaria (PK)
 * @property int $idpedidocli_central Foreign key a PEDIDOCLI_CENTRAL
 * @property int $idarticulo Foreign key a ARTICULO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_LPEDIDOCLI_CENT_PEDCLICENT (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPEDIDOCLI_CENTRAL
 *
 * PK_LPEDIDOCLI_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOCLI_CENTRAL
 */
class LpedidocliCentral extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidocli_central';

    protected $primaryKey = 'idlpedidocli_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idlpedidocli', 'idpedidocli_central', 'idpedidocli', 'idmovalm', 'idarticulo',
        'estado', 'unidades', 'not', 'freserva', 'fliberacion',
        'idusuariomod', 'pcosto', 'precio', 'dto', 'iva',
        'recargo', 'idtipomedida', 'idlpresupuestocli', 'unid', 'idlote',
        'seclote', 'notapieza', 'idlalbaranpro', 'notageneral', 'idlpedidocli_internet',
        'idbono_promocion', 'guiapertenencia', 'fguiapertenencia', 'ubicacion', 'ngrupo_segundamano',
        'idalmacen_creacion', 'parte_exenta', 'not', 'tarifa_genera_puntos', 'idcatalogo',
        'idlpedidodel', 'idalmacen_forzar_pedir',
    ];

    protected $casts = [
        'freserva' => 'datetime',
        'fliberacion' => 'datetime',
        'fguiapertenencia' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Artículo de la línea
     * ✅ ÓPTIMO: Usa PK_ARTICULO (ultra rápido)
     */
    public function articulo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Articulo\Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Pedido al que pertenece esta línea
     * ✅ ÓPTIMO: Usa PK_PEDIDOCLI_CCENTRAL (ultra rápido)
     * 💡 Usa idpedidocli_central para máxima performance
     */
    public function pedido()
    {
        return $this->belongsTo(PedidocliCentral::class, 'idpedidocli_central', 'idpedidocli_central');
    }

    /**
     * Relación: LpedidocliCentral
     * ✅ Usa PK_LPEDIDOCLI_CENTRAL (indexado)
     */
    public function lpedidocliCentral()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidocliCentral::class, 'idlpedidocli_central', 'idlpedidocli_central');
    }

    /**
     * Relación: Lpedidocli
     * ⚠️  SIN ÍNDICE en IDLPEDIDOCLI
     */
    public function lpedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\LpedidocliCapthaya::class, 'idlpedidocli', 'idlpedidocli');
    }

    /**
     * Relación: Tipomedida
     * ⚠️  SIN ÍNDICE en IDTIPOMEDIDA
     */
    public function tipomedida()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Configuracion\Tipomedida::class, 'idtipomedida', 'idtipomedida');
    }

    /**
     * Relación: Lote
     * ⚠️  SIN ÍNDICE en IDLOTE
     */
    public function lote()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Lote\Lote::class, 'idlote', 'idlote');
    }

    /**
     * Relación: Lalbaranpro
     * ⚠️  SIN ÍNDICE en IDLALBARANPRO
     */
    public function lalbaranpro()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Albaran\LalbaranproCapthaya::class, 'idlalbaranpro', 'idlalbaranpro');
    }

    /**
     * Relación: BonoPromocion
     * ⚠️  SIN ÍNDICE en IDBONO_PROMOCION
     */
    public function bonoPromocion()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Promocion\BonoPromocion::class, 'idbono_promocion', 'idbono_promocion');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Catalogo\Catalogo::class, 'idcatalogo', 'idcatalogo');
    }
}
