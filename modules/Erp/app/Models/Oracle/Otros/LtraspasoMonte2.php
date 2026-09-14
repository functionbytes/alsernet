<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Articulo\Articulo;
use Modules\Erp\Models\Oracle\Factura\LfacturacliCentral;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LTRASPASO_MONTE2
 *
 * ÍNDICES DISPONIBLES:
 * PK_LTRASPASO_MONTE2 (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLTRASPASO
 */
class LtraspasoMonte2 extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'ltraspaso_monte2';

    protected $primaryKey = 'idltraspaso';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idtraspaso', 'idmovalm', 'idarticulo', 'unidades', 'not',
        'idusuariomod', 'idlfacturacli', 'idlpedidodel', 'unidades_enviadas', 'observaciones',
        'numero_serie',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Ltraspaso
     * ✅ Usa PK_LTRASPASO_MONTE2 (indexado)
     */
    public function ltraspaso()
    {
        return $this->belongsTo(LtraspasoCapthaya::class, 'idltraspaso', 'idltraspaso');
    }

    /**
     * Relación: Traspaso
     * ⚠️  SIN ÍNDICE en IDTRASPASO
     */
    public function traspaso()
    {
        return $this->belongsTo(TraspasoCapthaya::class, 'idtraspaso', 'idtraspaso');
    }

    /**
     * Relación: Articulo
     * ⚠️  SIN ÍNDICE en IDARTICULO
     */
    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'idarticulo', 'idarticulo');
    }

    /**
     * Relación: Lfacturacli
     * ⚠️  SIN ÍNDICE en IDLFACTURACLI
     */
    public function lfacturacli()
    {
        return $this->belongsTo(LfacturacliCentral::class, 'idlfacturacli', 'idlfacturacli');
    }
}
