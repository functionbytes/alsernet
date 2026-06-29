<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_GRUPO_OBJETOS
 * Tabla de replicación/materialización de Oracle
 */
class MlogGrupoObjetos extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_grupo_objetos';

    protected $primaryKey = 'idgrupoobjeto';

    public $incrementing = false;

    public $timestamps = false;
}
