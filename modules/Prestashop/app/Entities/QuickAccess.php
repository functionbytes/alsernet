<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class QuickAccess extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_quick_access';

    protected $primaryKey = 'id_quick_access';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'link',
        'new_window',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
