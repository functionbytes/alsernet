<?php

namespace Modules\Erp\Models\Oracle\Configuracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla TRANSPORTISTA
 *
 * ÍNDICES DISPONIBLES:
 * PK_TRANSPORTISTA (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDTRANSPORTISTA
 */
class Transportista extends Model
{
    use SoftDeletes;
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'transportista';

    protected $primaryKey = 'idtransportista';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    const DELETED_AT = 'fbaja';

    protected $fillable = [
        'idusuariocre', 'idusuariomod', 'idusuariobaj', 'nombre', 'cif',
        'percontacto', 'telefono1', 'telefono2', 'fax', 'email',
        'codcliente', 'calle', 'num', 'localidad', 'provincia',
        'pais', 'cp', 'estado', 'idregfiscal', 'costoenviodefecto',
        'codigoconta', 'idformatoetiqenvio', 'tiposervicio', 'nombreimagen', 'enviasms',
        'retorno', 'agencia_cliente', 'codigo_cliente', 'interno', 'idalmacen',
        'permite_contrareembolso', 'login_ws', 'password_ws', 'url_ws', 'client_id',
        'secret_id', 'url_tracking',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // ========================================
    // Relaciones
    // ========================================

    /**
     * Relación con Almacen
     */
    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'idalmacen', 'idalmacen');
    }

    /**
     * Relación: Transportista
     * ✅ Usa PK_TRANSPORTISTA (indexado)
     */
    public function transportista()
    {
        return $this->belongsTo(Transportista::class, 'idtransportista', 'idtransportista');
    }

    /**
     * Relación: Regfiscal
     * ⚠️  SIN ÍNDICE en IDREGFISCAL
     */
    public function regfiscal()
    {
        return $this->belongsTo(Regfiscal::class, 'idregfiscal', 'idregfiscal');
    }
}
