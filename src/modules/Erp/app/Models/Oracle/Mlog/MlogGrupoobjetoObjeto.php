<?php

namespace Modules\Erp\Models\Oracle\Mlog;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla del sistema MLOG$_GRUPOOBJETO_OBJETO
 * Tabla de replicación/materialización de Oracle
 */
class MlogGrupoobjetoObjeto extends Model
{
    protected $connection = 'oracle';

    protected $table = 'mlog$_grupoobjeto_objeto';

    protected $primaryKey = 'idgrupoobjeto';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'idobjeto',
    ];
}
