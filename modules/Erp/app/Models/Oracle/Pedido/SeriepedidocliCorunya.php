<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla SERIEPEDIDOCLI_CORUNYA (Series de Pedidos)
 *
 * @property int $idseriepedidocli Clave primaria (PK)
 * @property string $descripcion Descripción larga de la serie
 * @property string $descripcorta Código corto de la serie (ej: "2025")
 * @property int $numero Número consecutivo actual
 *
 * ÍNDICES DISPONIBLES:
 * PK_SERIEPEDIDOCLI_CORUNYA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDSERIEPEDIDOCLI
 */
class SeriepedidocliCorunya extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'seriepedidocli_corunya';

    protected $primaryKey = 'idseriepedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'descripcion', 'descripcorta', 'idusuariocre', 'idusuariomod', 'idusuariobaj',
        'estado', 'numero', 'idempresa', 'fdesde', 'fhasta',
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
     * Relación: Seriepedidocli
     * ✅ Usa PK_SERIEPEDIDOCLI_CORUNYA (indexado)
     */
    public function seriepedidocli()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Pedido\SeriepedidocliCapthaya::class, 'idseriepedidocli', 'idseriepedidocli');
    }

    /**
     * Relación: Empresa
     * ⚠️  SIN ÍNDICE en IDEMPRESA
     */
    public function empresa()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Otros\Empresa::class, 'idempresa', 'idempresa');
    }
}
