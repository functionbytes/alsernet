<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Models\Oracle\Cliente\Cliente;
use Modules\Erp\Models\Oracle\Configuracion\Idioma;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CAMBIO_PUBLICIDAD_EMAIL
 *
 * ÍNDICES DISPONIBLES:
 * ✅ INDX_CAMBIOPUBLIE_IDCLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * ✅ INDX_PK_CAMBIOPUBLIE_CATAL (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCATALOGO
 *
 * ✅ INDX_PK_CAMBIOPUBLIE_EMAIL (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: EMAIL
 *
 * ✅ INDX_PK_CAMBIOPUBLIE_FECHA (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: FECHA
 *
 * PK_CAMBIO_PUBLICIDAD_EMAIL (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCAMBIO_PUBLICIDAD_EMAIL
 */
class CambioPublicidadEmail extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cambio_publicidad_email';

    protected $primaryKey = 'idcambio_publicidad_email';

    public $timestamps = false;

    protected $fillable = [
        'email', 'idcatalogo', 'fecha', 'enviar', 'observaciones',
        'ididioma', 'idcliente',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Catalogo
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
    }

    /**
     * Relación: CambioPublicidadEmail
     * ✅ Usa PK_CAMBIO_PUBLICIDAD_EMAIL (indexado)
     */
    public function cambioPublicidadEmail()
    {
        return $this->belongsTo(CambioPublicidadEmail::class, 'idcambio_publicidad_email', 'idcambio_publicidad_email');
    }

    /**
     * Relación: Idioma
     * ⚠️  SIN ÍNDICE en IDIDIOMA
     */
    public function idioma()
    {
        return $this->belongsTo(Idioma::class, 'ididioma', 'ididioma');
    }

    /**
     * Relación: Cliente
     * ✅ Usa INDX_CAMBIOPUBLIE_IDCLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }
}
