<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Otros\Empresa;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEPEDIDOPRO_CENTRAL
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEPEDIDOPRO_CENTRAL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEPEDIDOPRO_CENTRAL
 */
class SeriepedidoproCentral extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriepedidopro_central';

    protected $primaryKey = 'idseriepedidopro_central';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idseriepedidopro', 'descripcion', 'descripcorta', 'idusuariocre', 'idusuariomod',
        'idusuariobaj', 'estado', 'numero', 'idempresa', 'fdesde',
        'fhasta', 'idalmacen_creacion',
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
     * Relación: SeriepedidoproCentral
     * ✅ Usa PK_SERIEPEDIDOPRO_CENTRAL (indexado)
     */
    public function seriepedidoproCentral()
    {
        return $this->belongsTo(SeriepedidoproCentral::class, 'idseriepedidopro_central', 'idseriepedidopro_central');
    }

    /**
     * Relación: Seriepedidopro
     * ⚠️  SIN ÍNDICE en IDSERIEPEDIDOPRO
     */
    public function seriepedidopro()
    {
        return $this->belongsTo(SeriepedidoproCapthaya::class, 'idseriepedidopro', 'idseriepedidopro');
    }

    /**
     * Relación: Empresa
     * ⚠️  SIN ÍNDICE en IDEMPRESA
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'idempresa', 'idempresa');
    }
}
