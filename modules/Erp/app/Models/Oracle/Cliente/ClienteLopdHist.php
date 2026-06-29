<?php

namespace Modules\Erp\Models\Oracle\Cliente;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla CLIENTE_LOPD_HIST
 *
 * ÍNDICES DISPONIBLES:
 * PK_CLIENTE_LOPD_HIST (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDCLIENTE_LOPD_HIST
 */
class ClienteLopdHist extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'cliente_lopd_hist';

    protected $primaryKey = 'idcliente_lopd_hist';

    public $timestamps = false;

    protected $fillable = [
        'idcliente', 'nombre', 'cif', 'email', 'faceptacion_lopd',
        'idusuario_aceptacion_lopd', 'origen_aceptacion_lopd', 'idfotografia_firma_lopd', 'ubicacion_aceptacion_lopd', 'no_informacion_comercial_lopd',
        'no_datos_a_terceros_lopd', 'apellidos',
    ];

    protected $casts = [
        'faceptacion_lopd' => 'datetime',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación: ClienteLopdHist
     * ✅ Usa PK_CLIENTE_LOPD_HIST (indexado)
     */
    public function clienteLopdHist()
    {
        return $this->belongsTo(ClienteLopdHist::class, 'idcliente_lopd_hist', 'idcliente_lopd_hist');
    }

    /**
     * Relación: Cliente
     * ⚠️  SIN ÍNDICE en IDCLIENTE
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idcliente', 'idcliente');
    }
}
