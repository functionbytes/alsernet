<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Configuracion\Tipodireccion;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTEDIRECCION_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTEDIR_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTEDIR_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTEDIRECCION
 */
class Clientedireccion extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientedireccion_cent';

    protected $primaryKey = 'idclientedireccion';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idtipodireccion', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'codigopostal', 'poblacion', 'provincia', 'pais',
        'observacion', 'calle', 'num',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Clientedireccion
     * ✅ Usa PK_CLIENTEDIR_CENT (indexado)
     */
    public function clientedireccion()
    {
        return $this->belongsTo(Clientedireccion::class, 'idclientedireccion', 'idclientedireccion');
    }

    /**
     * Relación: Cliente
     * ✅ Usa FK_CLIENTEDIR_CENT__CLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Tipodireccion
     * ⚠️  SIN ÍNDICE en IDTIPODIRECCION
     */
    public function tipodireccion()
    {
        return $this->belongsTo(Tipodireccion::class, 'idtipodireccion', 'idtipodireccion');
    }
}
