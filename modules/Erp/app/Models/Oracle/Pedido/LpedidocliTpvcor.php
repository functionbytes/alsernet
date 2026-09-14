<?php

namespace Modules\Erp\Models\Oracle\Pedido;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla LPEDIDOCLI_TPVCOR
 *
 * ÍNDICES DISPONIBLES:
 * PK_LPEDIDOCLI_TPVCOR (UNIQUE)
 *    - Tipo: NORMAL
 *    - Columnas: IDLPEDIDOCLI
 */
class LpedidocliTpvcor extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'lpedidocli_tpvcor';

    protected $primaryKey = 'idlpedidocli';

    public $timestamps = true;

    const CREATED_AT = 'fcreacion';

    const UPDATED_AT = 'fmodificacion';

    protected $fillable = [
        'idpedidocli', 'idmovalm', 'idarticulo', 'estado', 'unidades',
        'not', 'freserva', 'fliberacion', 'idusuariomod', 'pcosto',
        'precio', 'dto', 'iva', 'recargo', 'idtipomedida',
        'idlpresupuestocli', 'unid', 'idlote', 'seclote', 'notapieza',
        'idlalbaranpro', 'notageneral', 'idlpedidocli_internet', 'idbono_promocion', 'guiapertenencia',
        'fguiapertenencia', 'ubicacion', 'ngrupo_segundamano', 'parte_exenta', 'not',
        'tarifa_genera_puntos', 'idcatalogo', 'idlpedidodel', 'idalmacen_forzar_pedir',
    ];

    protected $casts = [
        'freserva' => 'datetime',
        'fliberacion' => 'datetime',
        'fguiapertenencia' => 'datetime',
        'estado' => 'boolean',
    ];
}
