<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_lang';

    protected $primaryKey = 'id_lang';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'iso_code',
        'locale',
        'language_code',
    ];

    protected $casts = [
        'date_add' => 'datetime',
        'date_upd' => 'datetime',
    ];
}
