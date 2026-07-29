<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_attachment';

    protected $primaryKey = 'id_attachment';

    public $timestamps = false;

    protected $fillable = [
        'file',
        'file_name',
        'file_size',
        'name',
        'mime',
        'description',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];
}
