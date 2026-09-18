<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla PLATAFORMA_PAGO
 *
 * ÍNDICES DISPONIBLES:
 * PK_PLATAFORMA_PAGO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPLATAFORMA_PAGO
 *
 * ⚠️  UK_PLATAFORMA_PAGO_DESC (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: DESCRIPCION
 */
class PlataformaPago extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'plataforma_pago';

    protected $primaryKey = 'idplataforma_pago';

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
        return $this->hasMany(Formapago::class, 'idplataforma_pago', 'idplataforma_pago');
    }

    /**
     * Relación: PlataformaPago
     * ✅ Usa PK_PLATAFORMA_PAGO (indexado)
     */
    public function plataformaPago()
    {
        return $this->belongsTo(PlataformaPago::class, 'idplataforma_pago', 'idplataforma_pago');
    }
}
