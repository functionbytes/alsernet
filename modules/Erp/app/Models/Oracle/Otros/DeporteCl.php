<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;
use Modules\Supplier\Entities\SupplierSport;

/**
 * Modelo para la tabla DEPORTE_CL
 *
 * ÍNDICES DISPONIBLES:
 * PK_DEPORTE_CL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDDEPORTE_CL
 */
class DeporteCl extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'deporte_cl';

    protected $primaryKey = 'iddeporte_cl';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'idusuariocre', 'idusuariomod', 'idusuariobaj', 'descripcion',
        'desc_corta', 'prefijo', 'prefijo_segunda_mano', 'codigo_segunda_mano_automatico', 'idarticulo_segunda_mano',
        'idnumerador_segunda_mano',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: DeporteCl
     * ✅ Usa PK_DEPORTE_CL (indexado)
     */
    public function deporteCl()
    {
        return $this->belongsTo(DeporteCl::class, 'iddeporte_cl', 'iddeporte_cl');
    }

    /**
     * Relación inversa: SupplierSports (sincronización Supplier)
     */
    public function supplierSports(): HasMany
    {
        return $this->hasMany(SupplierSport::class, 'erp_sport_id', 'iddeporte_cl');
    }
}
