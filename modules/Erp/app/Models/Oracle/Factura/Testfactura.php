<?php

namespace Modules\Erp\Models\Oracle\Factura;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TESTFACTURA
 *
 * ÍNDICES DISPONIBLES:
 * PK_TESTFACTURA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTESTFACTURA
 */
class Testfactura extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'testfactura';

    protected $primaryKey = 'idtestfactura';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'estado', 'descripcion', 'idusuariomod',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Testfactura
     * ✅ Usa PK_TESTFACTURA (indexado)
     */
    public function testfactura()
    {
        return $this->belongsTo(Testfactura::class, 'idtestfactura', 'idtestfactura');
    }
}
