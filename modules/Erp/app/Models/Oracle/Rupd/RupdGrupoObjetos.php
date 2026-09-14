<?php

namespace Modules\Erp\Models\Oracle\Rupd;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema RUPD$_GRUPO_OBJETOS
 * Tabla de replicación/materialización de Oracle
 */
class RupdGrupoObjetos extends Model
{
    protected $connection = 'oracle';

    protected $table = 'rupd$_grupo_objetos';

    protected $primaryKey = 'idgrupoobjeto';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'snapid',
    ];
}
