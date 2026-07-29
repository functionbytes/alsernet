<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FORMAPAGO_METODO
 *
 * ÍNDICES DISPONIBLES:
 * PK_FORMAPAGO_METODO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFORMAPAGO_METODO
 */
class FormapagoMetodo extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'formapago_metodo';

    protected $primaryKey = 'idformapago_metodo';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación inversa con Formapago
     */
    public function formapagos()
    {
        return $this->hasMany(Formapago::class, 'idformapago_metodo', 'idformapago_metodo');
    }

    /**
     * Relación: FormapagoMetodo
     * ✅ Usa PK_FORMAPAGO_METODO (indexado)
     */
    public function formapagoMetodo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\FormapagoMetodo::class, 'idformapago_metodo', 'idformapago_metodo');
    }
}
