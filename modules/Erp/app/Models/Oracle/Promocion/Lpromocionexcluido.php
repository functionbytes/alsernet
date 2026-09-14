<?php

namespace Modules\Erp\Models\Oracle\Promocion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\SubfamiliaCl;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPROMOCIONEXCLUIDO
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPROMOCIONEXCLUIDO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPROMOCIONEXCLUIDO
 */
class Lpromocionexcluido extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpromocionexcluido';

    protected $primaryKey = 'idlpromocionexcluido';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idpromocion', 'idsubfamilia_cl', 'estado', 'idusuariocre', 'idusuariomod',
        'idusuariobaj',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Lpromocionexcluido
     * ✅ Usa PK_LPROMOCIONEXCLUIDO (indexado)
     */
    public function lpromocionexcluido()
    {
        return $this->belongsTo(Lpromocionexcluido::class, 'idlpromocionexcluido', 'idlpromocionexcluido');
    }

    /**
     * Relación: Promocion
     * ⚠️  SIN ÍNDICE en IDPROMOCION
     */
    public function promocion()
    {
        return $this->belongsTo(Promocion::class, 'idpromocion', 'idpromocion');
    }

    /**
     * Relación: SubfamiliaCl
     * ⚠️  SIN ÍNDICE en IDSUBFAMILIA_CL
     */
    public function subfamiliaCl()
    {
        return $this->belongsTo(SubfamiliaCl::class, 'idsubfamilia_cl', 'idsubfamilia_cl');
    }
}
