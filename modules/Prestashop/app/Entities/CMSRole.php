<?php

namespace Modules\Prestashop\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CMSRole extends Model
{
    protected $connection = 'prestashop';

    protected $table = 'aalv_cms_role';

    protected $primaryKey = 'id_cms_role';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'id_cms',
    ];

    protected $casts = [
        'id_cms' => 'integer',
    ];

    public function cms(): BelongsTo
    {
        return $this->belongsTo(CMS::class, 'id_cms');
    }
}
