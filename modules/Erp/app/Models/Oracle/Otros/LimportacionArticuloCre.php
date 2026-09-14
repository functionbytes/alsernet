<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Models\Oracle\Catalogo\Modelo;
use Modules\Erp\Models\Oracle\Configuracion\Impuesto;
use Modules\Erp\Models\Oracle\Proveedor\Artiprov;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LIMPORTACION_ARTICULO_CRE
 *
 * ÍNDICES DISPONIBLES:
 * PK_LIMPORTACION_ARTICULO_CRE (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLIMPORTACION_ARTICULO_CRE
 */
class LimportacionArticuloCre extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'limportacion_articulo_cre';

    protected $primaryKey = 'idlimportacion_articulo_cre';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idimportacion_articulo', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'seleccionado', 'fprocesado', 'idartiprov', 'codigopro', 'ean13',
        'upc', 'descripcion', 'preciorecomendadoprov_coniva', 'pcosto', 'dto1',
        'dto2', 'idimpuesto', 'pvp', 'idgrupo_cl', 'idcatalogo',
        'externo', 'visible_web', 'comparador_espana', 'comparador_portugal', 'etiquetas',
        'idmodelo', 'sufijo_codigo', 'comparador_francia', 'peso', 'etiq_electr_capthaya',
        'etiq_electr_ddleon', 'etiq_electr_tpvcor', 'intrastat', 'idcaracteristica1', 'idvalor_caracteristica1',
        'idcaracteristica2', 'idvalor_caracteristica2', 'idcaracteristica3', 'idvalor_caracteristica3',
    ];

    protected $casts = [
        'fprocesado' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: LimportacionArticuloCre
     * ✅ Usa PK_LIMPORTACION_ARTICULO_CRE (indexado)
     */
    public function limportacionArticuloCre()
    {
        return $this->belongsTo(LimportacionArticuloCre::class, 'idlimportacion_articulo_cre', 'idlimportacion_articulo_cre');
    }

    /**
     * Relación: ImportacionArticulo
     * ⚠️  SIN ÍNDICE en IDIMPORTACION_ARTICULO
     */
    public function importacionArticulo()
    {
        return $this->belongsTo(ImportacionArticulo::class, 'idimportacion_articulo', 'idimportacion_articulo');
    }

    /**
     * Relación: Artiprov
     * ⚠️  SIN ÍNDICE en IDARTIPROV
     */
    public function artiprov()
    {
        return $this->belongsTo(Artiprov::class, 'idartiprov', 'idartiprov');
    }

    /**
     * Relación: Impuesto
     * ⚠️  SIN ÍNDICE en IDIMPUESTO
     */
    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'idimpuesto', 'idimpuesto');
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
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
    }

    /**
     * Relación: Modelo
     * ⚠️  SIN ÍNDICE en IDMODELO
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'idmodelo', 'idmodelo');
    }
}
