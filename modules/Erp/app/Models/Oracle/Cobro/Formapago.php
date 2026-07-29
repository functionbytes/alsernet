<?php

namespace Modules\Erp\Models\Oracle\Cobro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla FORMAPAGO
 *
 * ÍNDICES DISPONIBLES:
 * ✅ IDX_FORMAPAGO_IDFORMAPAGO_METO (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFORMAPAGO_METODO
 *
 * ✅ IDX_FORMAPAGO_IDPLATAFORMA_PAG (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDPLATAFORMA_PAGO
 *
 * PK_FORMAPAGO (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDFORMAPAGO
 */
class Formapago extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'formapago';

    protected $primaryKey = 'idformapago_metodo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idformapago', 'estado', 'idusuariomod', 'diasprimero', 'ndias',
        'nplazos', 'contado', 'descripcion', 'conautorizacion', 'contarjeta',
        'codigoconta', 'contrarreembolso', 'idformapago_web', 'codigocontavencimiento', 'paraproveedor',
        'paracliente', 'codigo_mediopago', 'idplataforma_pago', 'validar_titular', 'confirmar_cobro',
        'excepcional', 'cobro_automatizado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con FormapagoMetodo
     */
    public function formapago_metodo()
    {
        return $this->belongsTo(FormapagoMetodo::class, 'idformapago_metodo', 'idformapago_metodo');
    }

    /**
     * Relación con PlataformaPago
     */
    public function plataforma_pago()
    {
        return $this->belongsTo(PlataformaPago::class, 'idplataforma_pago', 'idplataforma_pago');
    }

    /**
     * Relación: FormapagoMetodo
     * ✅ Usa IDX_FORMAPAGO_IDFORMAPAGO_METO (indexado)
     */
    public function formapagoMetodo()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\FormapagoMetodo::class, 'idformapago_metodo', 'idformapago_metodo');
    }

    /**
     * Relación: PlataformaPago
     * ✅ Usa IDX_FORMAPAGO_IDPLATAFORMA_PAG (indexado)
     */
    public function plataformaPago()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\PlataformaPago::class, 'idplataforma_pago', 'idplataforma_pago');
    }

    /**
     * Relación: Formapago
     * ✅ Usa PK_FORMAPAGO (indexado)
     */
    public function formapago()
    {
        return $this->belongsTo(\Modules\Erp\Models\Oracle\Cobro\Formapago::class, 'idformapago', 'idformapago');
    }
}
