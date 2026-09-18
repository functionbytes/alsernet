<?php

namespace Modules\Erp\Models\Oracle\Proveedor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierProductPrice;

/**
 * Modelo para la tabla ARTIPROV_TARIFAPRO
 *
 * ÍNDICES DISPONIBLES:
 * PK_ARTIPROV_TARIFAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTIPROV_TARIFAPRO
 *
 * ⚠️  UK_ARTIPROV_TARIFAPRO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDARTIPROV, ESTADO, FECHA
 */
class ArtiprovTarifapro extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'artiprov_tarifapro';

    protected $primaryKey = 'idartiprov_tarifapro';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idartiprov', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'estado',
        'fecha', 'pcosto', 'not', 'dto1', 'not',
        'dto2', 'not',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: ArtiprovTarifapro
     * ✅ Usa PK_ARTIPROV_TARIFAPRO (indexado)
     */
    public function artiprovTarifapro()
    {
        return $this->belongsTo(ArtiprovTarifapro::class, 'idartiprov_tarifapro', 'idartiprov_tarifapro');
    }

    /**
     * Relación: Artiprov
     * ✅ Usa UK_ARTIPROV_TARIFAPRO (indexado)
     */
    public function artiprov()
    {
        return $this->belongsTo(Artiprov::class, 'idartiprov', 'idartiprov');
    }

    /**
     * Relación inversa: SupplierProductPrices (sincronización Supplier)
     */
    public function supplierProductPrices(): HasMany
    {
        return $this->hasMany(SupplierProductPrice::class, 'erp_price_id', 'idartiprov_tarifapro');
    }
}
