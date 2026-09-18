<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Models\Oracle\Catalogo\Catalogo;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTECATALOGO_CENT
 *
 * ÍNDICES DISPONIBLES:
 * ✅ FK_CLIENTECAT_CENT__CLIENTE (NONUNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE
 *
 * PK_CLIENTECATALOGO_CENT (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTECATALOGO
 */
class ClientecatalogoCent extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'clientecatalogo_cent';

    protected $primaryKey = 'idclientecatalogo';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idcliente', 'idcatalogo', 'idusuariocre', 'idusuariobaj', 'idusuariomod',
        'estado', 'fsuscripcion',
    ];

    protected $casts = [
        'fsuscripcion' => 'datetime',
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: Clientecatalogo
     * ✅ Usa PK_CLIENTECATALOGO_CENT (indexado)
     */
    public function clientecatalogo()
    {
        return $this->belongsTo(ClientecatalogoCent::class, 'idclientecatalogo', 'idclientecatalogo');
    }

    /**
     * Relación: Cliente
     * ✅ Usa FK_CLIENTECAT_CENT__CLIENTE (indexado)
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }

    /**
     * Relación: Catalogo
     * ⚠️  SIN ÍNDICE en IDCATALOGO
     */
    public function catalogo()
    {
        return $this->belongsTo(Catalogo::class, 'idcatalogo', 'idcatalogo');
    }
}
