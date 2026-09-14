<?php

namespace Modules\Erp\Models\Oracle\Otros;

use Illuminate\Database\Eloquent\Model;
use Modules\Erp\Traits\UsesOCI8Performance;

/**
 * Modelo para la tabla REWRITE_TABLE
 */
class RewriteTable extends Model
{
    use UsesOCI8Performance;

    protected $connection = 'oracle';

    protected $table = 'rewrite_table';

    protected $primaryKey = 'statement_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'mv_owner', 'mv_name', 'sequence', 'query', 'message',
        'pass', 'flags', 'reserved1', 'reserved2',
    ];
}
